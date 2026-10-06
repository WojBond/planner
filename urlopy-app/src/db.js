const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const bcrypt = require('bcryptjs');

const DATA_DIR = path.join(__dirname, '..', 'data');
const DB_FILE = path.join(DATA_DIR, 'db.json');

function emptyDb() {
  return { users: [], groups: [], events: [] };
}

function load() {
  if (!fs.existsSync(DATA_DIR)) fs.mkdirSync(DATA_DIR, { recursive: true });
  if (!fs.existsSync(DB_FILE)) {
    const db = emptyDb();
    seed(db);
    save(db);
    return db;
  }
  const raw = fs.readFileSync(DB_FILE, 'utf-8');
  try {
    return JSON.parse(raw);
  } catch (e) {
    console.error('Nie udało się wczytac data/db.json, tworze nowa baze.', e);
    const db = emptyDb();
    seed(db);
    save(db);
    return db;
  }
}

function save(db) {
  fs.writeFileSync(DB_FILE, JSON.stringify(db, null, 2), 'utf-8');
}

function id() {
  return crypto.randomUUID();
}

function seed(db) {
  const groupId = id();
  db.groups.push({ id: groupId, name: 'Dział ogólny' });

  const adminPass = bcrypt.hashSync('admin123', 10);
  db.users.push({
    id: id(),
    name: 'Administrator',
    email: 'admin@firma.local',
    passwordHash: adminPass,
    role: 'admin',
    groupId: groupId,
    color: '#2563eb',
    mustChangePassword: true,
  });
}

const db = load();

function persist() {
  save(db);
}

module.exports = { db, persist, id };
