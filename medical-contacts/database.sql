-- SQLite Schema for Medical Contacts Module

CREATE TABLE IF NOT EXISTS doctors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    phone TEXT,
    email TEXT,
    fax TEXT
);

CREATE TABLE IF NOT EXISTS facilities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    address TEXT
);

CREATE TABLE IF NOT EXISTS body_parts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS doctor_facility (
    doctor_id INTEGER NOT NULL,
    facility_id INTEGER NOT NULL,
    PRIMARY KEY (doctor_id, facility_id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS doctor_facility_body_part (
    doctor_id INTEGER NOT NULL,
    facility_id INTEGER NOT NULL,
    body_part_id INTEGER NOT NULL,
    PRIMARY KEY (doctor_id, facility_id, body_part_id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE,
    FOREIGN KEY (body_part_id) REFERENCES body_parts(id) ON DELETE CASCADE
);
