const nodemailer = require('nodemailer');

const smtpConfigured = !!(process.env.SMTP_HOST && process.env.SMTP_USER && process.env.SMTP_PASS);

let transporter = null;
if (smtpConfigured) {
  transporter = nodemailer.createTransport({
    host: process.env.SMTP_HOST,
    port: Number(process.env.SMTP_PORT || 587),
    secure: process.env.SMTP_SECURE === 'true',
    auth: {
      user: process.env.SMTP_USER,
      pass: process.env.SMTP_PASS,
    },
  });
}

const MAIL_FROM = process.env.MAIL_FROM || 'Planer urlopów <urlopy@firma.local>';

async function sendMail({ to, subject, text }) {
  if (!to || to.length === 0) return;
  if (!transporter) {
    console.log('--- [MAIL - SMTP nieskonfigurowany, tylko log] ---');
    console.log('Do:', to);
    console.log('Temat:', subject);
    console.log(text);
    console.log('---------------------------------------------------');
    return;
  }
  try {
    await transporter.sendMail({
      from: MAIL_FROM,
      to,
      subject,
      text,
    });
  } catch (err) {
    console.error('Błąd wysyłki maila:', err.message);
  }
}

module.exports = { sendMail, smtpConfigured };
