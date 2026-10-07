require('dotenv').config();
const path = require('path');
const express = require('express');
const session = require('express-session');

const { attachUser } = require('./middleware/auth');
const authRoutes = require('./routes/auth');
const profileRoutes = require('./routes/profile');
const adminRoutes = require('./routes/admin');
const eventRoutes = require('./routes/events');
const { APP_VERSION } = require('./version');

const app = express();

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, '..', 'views'));
app.locals.appVersion = APP_VERSION;

app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(express.static(path.join(__dirname, '..', 'public')));

app.use(
  session({
    secret: process.env.SESSION_SECRET || 'dev-secret-zmień-mnie',
    resave: false,
    saveUninitialized: false,
    cookie: { httpOnly: true, maxAge: 1000 * 60 * 60 * 8 },
  })
);

app.use(attachUser);

app.use(authRoutes);
app.use(profileRoutes);
app.use(adminRoutes);
app.use(eventRoutes);

app.use((req, res) => {
  res.status(404).render('error', { message: 'Strona nie została znaleziona.' });
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log(`Planer urlopów v${APP_VERSION} działa na http://localhost:${PORT}`);
  console.log('Domyślne konto administratora: admin@firma.local / admin123 (zmień hasło po zalogowaniu)');
});
