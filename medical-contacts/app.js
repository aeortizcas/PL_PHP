let db = null;
let doctors = [];
let facilities = [];
let bodyParts = [];
let selectedDoctorId = null;

const BODY_PARTS = [
    'Head', 'Neck', 'Shoulders', 'Arms', 'Elbows', 'Forearms', 'Wrists',
    'Hands', 'Fingers', 'Chest', 'Upper Back', 'Lower Back', 'Abdomen',
    'Hips', 'Pelvis', 'Thighs', 'Knees', 'Legs', 'Ankles', 'Feet', 'Toes',
    'Spine', 'Jaw', 'TMJ'
];

function escape(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function toArray(arr) {
    return Array.from(arr);
}

function dbQuery(sql, params = []) {
    const stmt = db.prepare(sql);
    if (params.length) stmt.bind(params);
    const rows = [];
    while (stmt.step()) rows.push(stmt.getAsObject());
    stmt.free();
    return rows;
}

function dbRun(sql, params = []) {
    db.run(sql, params);
    saveDB();
}

function lastId() {
    return db.exec('SELECT last_insert_rowid() as id')[0].values[0][0];
}

function saveDB() {
    const data = db.export();
    const bytes = new Uint8Array(data);
    let binary = '';
    for (let i = 0; i < bytes.length; i++) binary += String.fromCharCode(bytes[i]);
    try {
        localStorage.setItem('medical_contacts_db', btoa(binary));
    } catch (e) {
        console.warn('Failed to save DB to localStorage:', e);
    }
}

function loadDB() {
    const saved = localStorage.getItem('medical_contacts_db');
    if (!saved) return null;
    try {
        const binary = atob(saved);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
        return bytes;
    } catch (e) {
        return null;
    }
}

function initSchema() {
    db.run(`
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
    `);
}

function seedBodyParts() {
    const existing = db.exec('SELECT COUNT(*) as c FROM body_parts');
    if (existing[0] && existing[0].values[0][0] > 0) return;
    const stmt = db.prepare('INSERT INTO body_parts (name) VALUES (?)');
    for (const name of BODY_PARTS) stmt.run([name]);
    stmt.free();
}

async function initApp() {
    const SQL = await initSqlJs({
        locateFile: file => `https://sql.js.org/dist/${file}`
    });

    const saved = loadDB();
    if (saved) {
        db = new SQL.Database(saved);
        if (db.exec("SELECT name FROM sqlite_master WHERE type='table' AND name='doctors'").length === 0) {
            initSchema();
            seedBodyParts();
            saveDB();
        }
    } else {
        const res = await fetch('medical-contacts.sqlite');
        if (res.ok) {
            const buf = await res.arrayBuffer();
            db = new SQL.Database(new Uint8Array(buf));
        } else {
            db = new SQL.Database();
            initSchema();
            seedBodyParts();
        }
        saveDB();
    }

    loadAll();
    bindEvents();
}

function loadAll() {
    facilities = dbQuery('SELECT * FROM facilities ORDER BY name');
    bodyParts = dbQuery('SELECT * FROM body_parts ORDER BY name');
    doctors = dbQuery(`
        SELECT d.*, GROUP_CONCAT(DISTINCT df.facility_id) as facility_ids
        FROM doctors d
        LEFT JOIN doctor_facility df ON df.doctor_id = d.id
        GROUP BY d.id
        ORDER BY d.last_name, d.first_name
    `);
    renderDoctorList();
    renderFacilitiesList();
    if (selectedDoctorId) {
        const stillExists = doctors.find(d => d.id === selectedDoctorId);
        if (stillExists) selectDoctor(selectedDoctorId);
        else { selectedDoctorId = null; renderDetail(null); }
    } else if (doctors.length > 0) {
        selectDoctor(doctors[0].id);
    } else {
        renderDetail(null);
    }
}

function renderDoctorList() {
    const container = document.getElementById('doctorList');
    const search = document.getElementById('searchInput').value.toLowerCase();
    const filtered = doctors.filter(d =>
        `${d.first_name} ${d.last_name}`.toLowerCase().includes(search) ||
        (d.email || '').toLowerCase().includes(search)
    );
    container.innerHTML = filtered.map(d => `
        <div class="doctor-item ${d.id === selectedDoctorId ? 'active' : ''}"
             onclick="selectDoctor(${d.id})">
            <div class="name">${escape(d.first_name)} ${escape(d.last_name)}</div>
            <div class="sub">${d.email || d.phone || ''}</div>
        </div>
    `).join('') || '<div style="padding:1rem;color:var(--text-muted)">No doctors found</div>';
}

function renderDetail(doctor, facilitiesData) {
    const container = document.getElementById('doctorDetail');
    if (!doctor) {
        container.innerHTML = '<div class="empty-state">Select a doctor</div>';
        return;
    }
    const facHtml = !facilitiesData ? '<p style="color:var(--text-muted)">Loading...</p>'
        : facilitiesData.length === 0 ? '<p style="color:var(--text-muted)">No facilities assigned</p>'
        : facilitiesData.map(f => {
            const bpIds = f.body_part_ids ? f.body_part_ids.split(',').map(Number) : [];
            const names = bpIds.map(id => bodyParts.find(bp => bp.id === id)).filter(Boolean);
            return `
                <div class="facility-card">
                    <h4>${escape(f.name)}</h4>
                    <div class="address">${escape(f.address || 'No address')}</div>
                    <div class="body-part-tags">
                        ${names.map(bp => `<span class="body-part-tag">${escape(bp.name)}</span>`).join('') || '<span style="font-size:0.8rem;color:var(--text-muted)">No body parts specified</span>'}
                    </div>
                </div>
            `;
        }).join('');
    container.innerHTML = `
        <div class="doctor-card">
            <h2>${escape(doctor.first_name)} ${escape(doctor.last_name)}</h2>
            <div class="doctor-actions">
                <button class="btn btn-primary" onclick="openDoctorModal(${doctor.id})">Edit</button>
                <button class="btn btn-secondary danger" onclick="deleteDoctor(${doctor.id})">Delete</button>
            </div>
            <div class="info-grid">
                <div class="info-item"><label>Phone</label><p>${doctor.phone || '—'}</p></div>
                <div class="info-item"><label>Email</label><p>${doctor.email || '—'}</p></div>
                <div class="info-item"><label>Fax</label><p>${doctor.fax || '—'}</p></div>
            </div>
            <h3 style="margin-top:1.5rem;font-size:1rem;">Facilities</h3>
            ${facHtml}
        </div>
    `;
}

function selectDoctor(id) {
    selectedDoctorId = id;
    renderDoctorList();
    const doctor = doctors.find(d => d.id === id);
    const facRows = dbQuery(`
        SELECT f.*, GROUP_CONCAT(dfbp.body_part_id) as body_part_ids
        FROM facilities f
        JOIN doctor_facility df ON df.facility_id = f.id AND df.doctor_id = ?
        LEFT JOIN doctor_facility_body_part dfbp ON dfbp.facility_id = f.id AND dfbp.doctor_id = ?
        GROUP BY f.id
    `, [id, id]);
    renderDetail(doctor, facRows);
}

function deleteDoctor(id) {
    if (!confirm('Delete this doctor?')) return;
    dbRun('DELETE FROM doctors WHERE id = ?', [id]);
    if (selectedDoctorId === id) { selectedDoctorId = null; }
    loadAll();
}

function openDoctorModal(id = null) {
    document.getElementById('doctorModal').classList.add('open');
    document.getElementById('editDoctorId').value = id || '';
    if (id) {
        const d = doctors.find(doc => doc.id === id);
        document.getElementById('doctorModalTitle').textContent = 'Edit Doctor';
        document.getElementById('doctorFirstName').value = d.first_name;
        document.getElementById('doctorLastName').value = d.last_name;
        document.getElementById('doctorPhone').value = d.phone || '';
        document.getElementById('doctorEmail').value = d.email || '';
        document.getElementById('doctorFax').value = d.fax || '';
    } else {
        document.getElementById('doctorModalTitle').textContent = 'New Doctor';
        ['doctorFirstName','doctorLastName','doctorPhone','doctorEmail','doctorFax'].forEach(id => document.getElementById(id).value = '');
    }
    renderFacilityCheckboxes(id);
}

function renderFacilityCheckboxes(doctorId) {
    const container = document.getElementById('doctorFacilitiesContainer');
    if (facilities.length === 0) {
        container.innerHTML = '<p style="color:var(--text-muted)">No facilities yet. Add one first.</p>';
        return;
    }
    let doctorFacilities = {};
    if (doctorId) {
        const rows = dbQuery(`
            SELECT facility_id, GROUP_CONCAT(body_part_id) as bp_ids
            FROM doctor_facility_body_part WHERE doctor_id = ?
            GROUP BY facility_id
        `, [doctorId]);
        for (const r of rows) {
            doctorFacilities[r.facility_id] = r.bp_ids ? r.bp_ids.split(',').map(Number) : [];
        }
    }
    container.innerHTML = facilities.map(f => {
        const selected = doctorFacilities[f.id] || [];
        return `
            <div class="facility-entry">
                <h4>${escape(f.name)} <span style="font-weight:400;font-size:0.8rem;color:var(--text-muted)">${escape(f.address || '')}</span></h4>
                <div class="bp-grid">
                    ${bodyParts.map(bp => `
                        <label class="bp-option ${selected.includes(bp.id) ? 'selected' : ''}"
                               data-facility="${f.id}" data-bp="${bp.id}">
                            <input type="checkbox" ${selected.includes(bp.id) ? 'checked' : ''}>
                            ${escape(bp.name)}
                        </label>
                    `).join('')}
                </div>
            </div>
        `;
    }).join('');
}

function collectDoctorForm() {
    const id = document.getElementById('editDoctorId').value;
    const first = document.getElementById('doctorFirstName').value.trim();
    const last = document.getElementById('doctorLastName').value.trim();
    if (!first || !last) { alert('First and last name are required'); return null; }
    const data = {
        first_name: first, last_name: last,
        phone: document.getElementById('doctorPhone').value.trim() || null,
        email: document.getElementById('doctorEmail').value.trim() || null,
        fax: document.getElementById('doctorFax').value.trim() || null,
        facilities: []
    };
    document.querySelectorAll('.facility-entry').forEach(entry => {
        const checked = entry.querySelectorAll('input[type="checkbox"]:checked');
        if (checked.length === 0) return;
        const facId = parseInt(checked[0].closest('.bp-option').dataset.facility);
        data.facilities.push({
            facility_id: facId,
            body_part_ids: toArray(checked).map(cb => parseInt(cb.closest('.bp-option').dataset.bp))
        });
    });
    return { id, data };
}

function saveDoctor() {
    const result = collectDoctorForm();
    if (!result) return;
    let { id, data } = result;

    if (id) {
        dbRun('UPDATE doctors SET first_name=?, last_name=?, phone=?, email=?, fax=? WHERE id=?',
            [data.first_name, data.last_name, data.phone, data.email, data.fax, parseInt(id)]);
        dbRun('DELETE FROM doctor_facility_body_part WHERE doctor_id=?', [parseInt(id)]);
        dbRun('DELETE FROM doctor_facility WHERE doctor_id=?', [parseInt(id)]);
    } else {
        dbRun('INSERT INTO doctors (first_name,last_name,phone,email,fax) VALUES (?,?,?,?,?)',
            [data.first_name, data.last_name, data.phone, data.email, data.fax]);
        id = lastId();
    }

    const doctorId = parseInt(id);
    for (const fac of data.facilities) {
        dbRun('INSERT OR IGNORE INTO doctor_facility (doctor_id,facility_id) VALUES (?,?)', [doctorId, fac.facility_id]);
        for (const bpId of fac.body_part_ids) {
            dbRun('INSERT OR IGNORE INTO doctor_facility_body_part (doctor_id,facility_id,body_part_id) VALUES (?,?,?)',
                [doctorId, fac.facility_id, bpId]);
        }
    }

    closeDoctorModal();
    loadAll();
}

function closeDoctorModal() {
    document.getElementById('doctorModal').classList.remove('open');
}

function openFacilityModal(id = null) {
    document.getElementById('facilityModal').classList.add('open');
    document.getElementById('editFacilityId').value = id || '';
    document.getElementById('facilityDeleteBtn').style.display = id ? 'inline-block' : 'none';
    if (id) {
        const f = facilities.find(fac => fac.id === id);
        document.getElementById('facilityModalTitle').textContent = 'Edit Facility';
        document.getElementById('facilityName').value = f.name;
        document.getElementById('facilityAddress').value = f.address || '';
    } else {
        document.getElementById('facilityModalTitle').textContent = 'New Facility';
        document.getElementById('facilityName').value = '';
        document.getElementById('facilityAddress').value = '';
    }
}

function closeFacilityModal() {
    document.getElementById('facilityModal').classList.remove('open');
}

function saveFacility() {
    const id = document.getElementById('editFacilityId').value;
    const name = document.getElementById('facilityName').value.trim();
    const address = document.getElementById('facilityAddress').value.trim() || null;
    if (!name) { alert('Facility name is required'); return; }
    if (id) {
        dbRun('UPDATE facilities SET name=?, address=? WHERE id=?', [name, address, parseInt(id)]);
    } else {
        dbRun('INSERT INTO facilities (name,address) VALUES (?,?)', [name, address]);
    }
    closeFacilityModal();
    loadAll();
}

function deleteFacility(id) {
    if (!confirm('Delete this facility?')) return;
    dbRun('DELETE FROM facilities WHERE id=?', [id]);
    closeFacilityModal();
    loadAll();
}

function renderFacilitiesList() {
    const container = document.getElementById('facilitiesList');
    container.innerHTML = facilities.map(f => `
        <div class="facility-row" onclick="openFacilityModal(${f.id})">
            <div>
                <div class="fac-name">${escape(f.name)}</div>
                <div class="fac-addr">${escape(f.address || 'No address')}</div>
            </div>
        </div>
    `).join('') || '<p style="color:var(--text-muted)">No facilities</p>';
}

function downloadDB() {
    const data = db.export();
    const blob = new Blob([data], { type: 'application/x-sqlite3' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'medical-contacts.sqlite';
    a.click();
    URL.revokeObjectURL(url);
}

function uploadDB(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        const bytes = new Uint8Array(e.target.result);
        db.close();
        db = new SQL.Database(bytes);
        saveDB();
        loadAll();
    };
    reader.readAsArrayBuffer(file);
}

function bindEvents() {
    document.querySelectorAll('.nav-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById(btn.dataset.tab + 'Tab').classList.add('active');
        });
    });

    document.getElementById('searchInput').addEventListener('input', renderDoctorList);
    document.getElementById('addDoctorBtn').addEventListener('click', () => openDoctorModal(null));
    document.getElementById('addFacilityBtn').addEventListener('click', () => openFacilityModal(null));

    document.getElementById('doctorModalSave').addEventListener('click', saveDoctor);
    document.getElementById('doctorModalCancel').addEventListener('click', closeDoctorModal);
    document.getElementById('doctorModalClose').addEventListener('click', closeDoctorModal);

    document.getElementById('facilityModalSave').addEventListener('click', saveFacility);
    document.getElementById('facilityModalCancel').addEventListener('click', closeFacilityModal);
    document.getElementById('facilityModalClose').addEventListener('click', closeFacilityModal);
    document.getElementById('facilityDeleteBtn').addEventListener('click', () => {
        deleteFacility(parseInt(document.getElementById('editFacilityId').value));
    });

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => {
            if (e.target === overlay) overlay.classList.remove('open');
        });
    });

    document.getElementById('downloadDbBtn').addEventListener('click', downloadDB);
    document.getElementById('uploadDbBtn').addEventListener('click', () => {
        document.getElementById('dbFileInput').click();
    });
    document.getElementById('dbFileInput').addEventListener('change', e => {
        if (e.target.files[0]) uploadDB(e.target.files[0]);
    });

    document.getElementById('doctorFacilitiesContainer').addEventListener('change', e => {
        if (e.target.matches('input[type="checkbox"]')) {
            e.target.closest('.bp-option').classList.toggle('selected', e.target.checked);
        }
    });
}

document.addEventListener('DOMContentLoaded', initApp);
