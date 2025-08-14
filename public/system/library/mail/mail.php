<?php
namespace Mail;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Mail
{
	public function send(): void
	{
		$mail = new PHPMailer(true);  // true - для включения исключений

		try {

			$mail->CharSet = PHPMailer::CHARSET_UTF8;
			
			// Настройки сервера (если используется SMTP)
			// $mail->isSMTP();
			// $mail->Host = 'smtp.example.com';
			// $mail->SMTPAuth = true;
			// $mail->Username = 'user@example.com';
			// $mail->Password = 'secret';
			// $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
			// $mail->Port = 465;

			
			// Отправитель
			$mail->setFrom($this->from, $this->sender);


			// Получатели
			if (is_array($this->to)) {
				foreach ($this->to as $recipient) {
					$mail->addAddress($recipient);
				}
			} else {
				$mail->addAddress($this->to);
			}

			// Ответить кому (если указано)
			if ($this->reply_to) {
				$mail->addReplyTo($this->reply_to);
			}

			// Тема письма
			$mail->Subject = $this->subject;

			// Тело письма
			if ($this->html) {
				$mail->isHTML(true);
				$mail->Body = $this->html;
				$mail->AltBody = $this->text ?: 'This is a HTML email. Please use an HTML compatible email viewer.';
			} else {
				$mail->isHTML(false);
				$mail->Body = $this->text;
			}

			// Вложения
			foreach ($this->attachments as $attachment) {
				if (file_exists($attachment)) {
					$mail->addAttachment($attachment);
				}
			}

			// Отправка
			$mail->send();
		} catch (Exception $e) {
			// Обработка ошибки (можно логировать или выбросить исключение)
			throw new Exception("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
		}
	}
}
