# Medical Contacts Module - Notes

## Requirements
- Store doctor contacts: first name, last name, phone, email, fax
- Facilities with addresses
- Body parts treated at each facility
- A doctor can attend many facilities
- A doctor can treat different body parts at different facilities
- A doctor may treat e.g. hands in facility A but NOT in facility B

## Data Model
- **doctors**: id, first_name, last_name, phone, email, fax
- **facilities**: id, name, address
- **body_parts**: id, name
- **doctor_facility**: many-to-many (doctor_id, facility_id)
- **doctor_facility_body_part**: which body parts a doctor treats at each facility (doctor_id, facility_id, body_part_id)
