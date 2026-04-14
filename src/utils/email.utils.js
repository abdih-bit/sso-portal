const nodemailer = require('nodemailer');

const transporter = nodemailer.createTransport({
  host: process.env.SMTP_HOST,
  port: parseInt(process.env.SMTP_PORT) || 587,
  secure: false,
  auth: {
    user: process.env.SMTP_USER,
    pass: process.env.SMTP_PASS,
  },
});

/**
 * Kirim email reset password
 */
async function sendPasswordResetEmail(email, fullName, resetToken) {
  const resetUrl = `${process.env.APP_URL}/reset-password?token=${resetToken}`;

  const mailOptions = {
    from: `"SSO Portal HQ Medan" <${process.env.SMTP_FROM}>`,
    to: email,
    subject: 'Reset Password - SSO Portal HQ Medan',
    html: `
      <!DOCTYPE html>
      <html>
      <body style="font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px;">
        <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; padding: 30px;">
          <div style="text-align: center; margin-bottom: 30px;">
            <h2 style="color: #1e40af;">SSO Portal HQ Medan</h2>
          </div>
          <h3>Halo, ${fullName}!</h3>
          <p>Kami menerima permintaan untuk mereset password akun Anda.</p>
          <p>Klik tombol di bawah ini untuk mereset password Anda:</p>
          <div style="text-align: center; margin: 30px 0;">
            <a href="${resetUrl}" 
               style="background: #1e40af; color: white; padding: 12px 30px; 
                      border-radius: 5px; text-decoration: none; font-size: 16px;">
              Reset Password
            </a>
          </div>
          <p style="color: #666; font-size: 14px;">
            Link ini akan kadaluarsa dalam <strong>1 jam</strong>.
          </p>
          <p style="color: #666; font-size: 14px;">
            Jika Anda tidak meminta reset password, abaikan email ini.
          </p>
          <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
          <p style="color: #999; font-size: 12px; text-align: center;">
            © 2025 HQ Medan. All rights reserved.<br>
            <a href="https://portal.hqmedan.com">portal.hqmedan.com</a>
          </p>
        </div>
      </body>
      </html>
    `,
  };

  return await transporter.sendMail(mailOptions);
}

module.exports = { sendPasswordResetEmail };
