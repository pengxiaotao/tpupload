<?php
class SMTP {
    private $host;
    private $port;
    private $security;
    private $username;
    private $password;
    private $smtp_conn;

    public function __construct($host, $port, $security, $username, $password) {
        $this->host = $host;
        $this->port = $port;
        $this->security = $security;
        $this->username = $username;
        $this->password = $password;
        $this->connect();
    }

    private function connect() {
        $address = ($this->security === 'ssl')? 'ssl://'. $this->host : $this->host;
        $this->smtp_conn = @fsockopen($address, $this->port, $errno, $errstr, 30);

        if (!$this->smtp_conn) {
            throw new Exception("无法连接到 SMTP 服务器: $errstr ($errno)");
        }

        $response = $this->getResponse();
        if (!$this->checkResponse($response, 220)) {
            throw new Exception("SMTP 服务器响应错误: $response");
        }

        $this->sendCommand('EHLO localhost');

        if ($this->security === 'tls') {
            $this->sendCommand('STARTTLS');
            stream_socket_enable_crypto($this->smtp_conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->sendCommand('EHLO localhost');
        }

        $this->sendCommand('AUTH LOGIN');
        $this->sendCommand(base64_encode($this->username));
        $this->sendCommand(base64_encode($this->password));
    }

    private function sendCommand($command) {
        fwrite($this->smtp_conn, $command. "\r\n");
        $response = $this->getResponse();
        $code = intval(substr($response, 0, 3));

        // 特殊处理 AUTH LOGIN 过程中的 334 响应
        if (in_array($command, ['AUTH LOGIN', base64_encode($this->username)]) && $code === 334) {
            return $response;
        }

        if (!$this->checkResponse($response, [235, 250])) {
            throw new Exception("SMTP 命令执行错误: $command, 响应: $response");
        }

        return $response;
    }

    private function getResponse() {
        $response = '';
        while ($line = fgets($this->smtp_conn, 512)) {
            $response.= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return $response;
    }

    private function checkResponse($response, $expectedCodes) {
        if (!is_array($expectedCodes)) {
            $expectedCodes = [$expectedCodes];
        }
        $code = intval(substr($response, 0, 3));
        return in_array($code, $expectedCodes);
    }

    public function send($to, $subject, $message, $headers) {
        try {
            $this->sendCommand("MAIL FROM: <{$this->username}>");
            $this->sendCommand("RCPT TO: <$to>");
            $this->sendCommand("DATA");

            fwrite($this->smtp_conn, "Subject: $subject\r\n");
            fwrite($this->smtp_conn, $headers);
            fwrite($this->smtp_conn, "\r\n");
            fwrite($this->smtp_conn, $message);
            fwrite($this->smtp_conn, "\r\n.\r\n");

            $response = $this->getResponse();
            if (!$this->checkResponse($response, 250)) {
                throw new Exception("邮件发送失败: $response");
            }

            $this->sendCommand("QUIT");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function __destruct() {
        if ($this->smtp_conn) {
            fclose($this->smtp_conn);
        }
    }
}