<?php
class RouterOSClient {
    private $ip, $user, $pass, $socket;

    public function __construct($ip, $user, $pass) {
        $this->ip = trim($ip);
        $this->user = $user;
        $this->pass = $pass;
    }

public function connect() {
    $this->socket = fsockopen($this->ip, 8728, $errno, $errstr, 3);
    if (!$this->socket) {
        throw new Exception("Gagal konek ke router: $errstr ($errno)");
    }

    stream_set_timeout($this->socket, 3);

    // Login pertama (ambil challenge)
    $this->write('/login', true);
    $response = $this->read();

    $challenge = '';
    foreach ($response as $line) {
        if (strpos($line, '=ret=') !== false) {
            $challenge = substr($line, 5); // fix: 5 karena '=ret=' panjangnya 5
            break;
        }
    }

    if (!$challenge) {
        throw new Exception("Login gagal: challenge kosong");
    }

    $md5 = md5(chr(0) . $this->pass . hex2bin($challenge), true);
    $responseHex = '00' . bin2hex($md5);

    // Kirim login kedua
    $this->write('/login', false);
    $this->write('=name=' . $this->user, false);
    $this->write('=response=' . $responseHex, true);

    $res = $this->read();
    if (!in_array('!done', $res)) {
        throw new Exception("Login gagal");
    }
}

    public function comm($command, $params = []) {
        $this->write($command, false);
        foreach ($params as $key => $value) {
            $this->write("={$key}={$value}", false);
        }
        $this->write('', true);
        $raw = $this->read();
        return $this->parseResponse($raw);
    }

    private function parseResponse($raw) {
        $parsed = [];
        $current = [];

        foreach ($raw as $line) {
            if ($line === '!re') {
                if (!empty($current)) {
                    $parsed[] = $current;
                    $current = [];
                }
            } elseif (strpos($line, '=') === 0) {
                $parts = explode('=', $line, 3);
                if (count($parts) === 3) {
                    $current[$parts[1]] = $parts[2];
                }
            }
        }

        if (!empty($current)) {
            $parsed[] = $current;
        }

        return $parsed;
    }

    public function write($command, $last = true) {
        $len = strlen($command);
        $this->writeLength($len);
        fwrite($this->socket, $command);
        if ($last) {
            fwrite($this->socket, chr(0));
        }
    }

    private function writeLength($length) {
        if ($length < 0x80) {
            fwrite($this->socket, chr($length));
        } elseif ($length < 0x4000) {
            $length |= 0x8000;
            fwrite($this->socket, chr(($length >> 8) & 0xFF));
            fwrite($this->socket, chr($length & 0xFF));
        } elseif ($length < 0x200000) {
            $length |= 0xC00000;
            fwrite($this->socket, chr(($length >> 16) & 0xFF));
            fwrite($this->socket, chr(($length >> 8) & 0xFF));
            fwrite($this->socket, chr($length & 0xFF));
        } elseif ($length < 0x10000000) {
            $length |= 0xE0000000;
            fwrite($this->socket, chr(($length >> 24) & 0xFF));
            fwrite($this->socket, chr(($length >> 16) & 0xFF));
            fwrite($this->socket, chr(($length >> 8) & 0xFF));
            fwrite($this->socket, chr($length & 0xFF));
        } else {
            fwrite($this->socket, chr(0xF0));
            fwrite($this->socket, chr(($length >> 24) & 0xFF));
            fwrite($this->socket, chr(($length >> 16) & 0xFF));
            fwrite($this->socket, chr(($length >> 8) & 0xFF));
            fwrite($this->socket, chr($length & 0xFF));
        }
    }

    public function read() {
        $response = [];
        while (true) {
            $len = $this->readLength();
            if ($len == 0) break;
            $response[] = fread($this->socket, $len);
        }
        return $response;
    }

    private function readLength() {
        $c = ord(fread($this->socket, 1));
        if (($c & 0x80) == 0x00) {
            return $c;
        } elseif (($c & 0xC0) == 0x80) {
            $c2 = ord(fread($this->socket, 1));
            return (($c & ~0xC0) << 8) + $c2;
        } elseif (($c & 0xE0) == 0xC0) {
            $c2 = ord(fread($this->socket, 1));
            $c3 = ord(fread($this->socket, 1));
            return (($c & ~0xE0) << 16) + ($c2 << 8) + $c3;
        } elseif (($c & 0xF0) == 0xE0) {
            $c2 = ord(fread($this->socket, 1));
            $c3 = ord(fread($this->socket, 1));
            $c4 = ord(fread($this->socket, 1));
            return (($c & ~0xF0) << 24) + ($c2 << 16) + ($c3 << 8) + $c4;
        } elseif ($c == 0xF0) {
            $c1 = ord(fread($this->socket, 1));
            $c2 = ord(fread($this->socket, 1));
            $c3 = ord(fread($this->socket, 1));
            $c4 = ord(fread($this->socket, 1));
            return ($c1 << 24) + ($c2 << 16) + ($c3 << 8) + $c4;
        }
        return 0;
    }

    // Placeholder
    public function addUser($username, $password, $profile) {}
    public function topupUser($username, $profile) {}
    public function disconnectUser($username) {}
    public function disableUser($username) {}
    public function getUserUptime($username) { return '1h5m'; }
}
