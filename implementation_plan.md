# Real SMTP Email Verification System Implementation Plan

We will install `PHPMailer` and update the application configuration to connect to a real SMTP server (like Gmail) using credentials defined in the local `.env` file. This allows real e-mails to be delivered to your inbox.

---

## User Action Required

> [!IMPORTANT]
> **Gmail App Password (Uygulama Şifresi):** 
> To send emails via your Gmail account safely, you need to generate an **App Password**:
> 1. Go to your **[Google Account Security Settings](https://myaccount.google.com/security)**.
> 2. Search for **"Uygulama şifreleri"** (App passwords) in the search bar.
> 3. Click it, choose a custom name (e.g. "eduQR"), and click **Oluştur** (Create).
> 4. Google will show you a **16-letter password** (e.g., `abcd efgh ijkl mnop`). Copy it!
> 5. You will paste this password into your `.env` file under `SMTP_PASS`!

---

## Proposed Changes

### 1. Project Dependencies

* We will install **PHPMailer** by executing:
  `composer require phpmailer/phpmailer`

---

### 2. Configuration Settings

#### [MODIFY] [.env](file:///c:/laragon/www/eduqr-rebuild/.env)
* Add the following keys at the bottom of the file (you will fill in your credentials):
  ```env
  # --- Mail (SMTP) ---------------------------------------------
  SMTP_HOST=smtp.gmail.com
  SMTP_PORT=465
  SMTP_USER=akyaldilek853@gmail.com
  SMTP_PASS=your-16-character-app-password
  SMTP_SECURE=ssl
  SMTP_FROM_NAME="eduQR Platformu"
  ```

---

### 3. Controller Code Integration

#### [MODIFY] [AuthController.php](file:///c:/laragon/www/eduqr-rebuild/src/Controllers/AuthController.php)
* Replace the `sendVerificationEmail` method to use **PHPMailer**:
  - Connect to the SMTP server using host, port, username, password, and secure protocol from the `.env` configuration.
  - Send HTML/text email securely using SMTP.
  - Keep logging to `temp_mail.txt` as a fallback.

---

## Verification Plan

### Manual Verification
1. Run `composer require phpmailer/phpmailer` in the terminal.
2. Edit `.env` with your email and Gmail app password.
3. Open the registration page and sign up.
4. Verify that a real email arrives in your Gmail inbox containing the 6-digit verification code.
