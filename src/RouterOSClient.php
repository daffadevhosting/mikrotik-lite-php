<?php
namespace MikroTikLite;

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
            throw new \Exception("Gagal konek ke router: $errstr ($errno)");
        }
        stream_set_timeout($this->socket, 3);

        $this->write('/login', false);
        $this->write('=name=' . $this->user, false);
        $this->write('=password=' . $this->pass, true);
        $response = $this->read();

        if (isset($response[0]) && strpos($response[0], '!done') !== false) {
            return true;
        }

        // Login lama
        fclose($this->socket);
        $this->socket = fsockopen($this->ip, 8728, $errno, $errstr, 3);
        if (!$this->socket) {
            throw new \Exception("Gagal konek ke router (ulang): $errstr ($errno)");
        }
        stream_set_timeout($this->socket, 3);

        $this->write('/login', true);
        $response = $this->read();

        if (!isset($response[0]) || !preg_match('/=ret=(.+)/', implode("\n", $response), $matches)) {
            throw new \Exception("Gagal mendapatkan challenge dari router");
        }

        $challenge = hex2bin($matches[1]);
        $md5 = md5("\x00" . $this->pass . $challenge, true);

        $this->write('/login', false);
        $this->write('=name=' . $this->user, false);
        $this->write('=response=00' . bin2hex($md5), true);

        $response = $this->read();
        if (!isset($response[0]) || strpos($response[0], '!done') === false) {
            throw new \Exception("Login gagal (mode lama)");
        }
    }

    public function comm($command, $params = []) {
        $this->write($command, false);
        foreach ($params as $key => $value) {
            $this->write("={$key}={$value}", false);
        }
        $this->write('', true);
        return $this->parseResponse($this->read());
    }

    public function getSystemInfo() {
        $info = [];

        $identity = $this->comm('/system/identity/print');
        if (isset($identity[0]['name'])) {
            $info['Identity'] = $identity[0]['name'];
        }

        $resource = $this->comm('/system/resource/print');
        if (isset($resource[0])) {
            $info['Version'] = $resource[0]['version'] ?? '';
            $info['Board Name'] = $resource[0]['board-name'] ?? '';
            $info['Architecture'] = $resource[0]['architecture-name'] ?? '';
            $info['Uptime'] = $resource[0]['uptime'] ?? '';
        }

        return $info;
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
            } elseif ($line === '!done') {
                if (!empty($current)) {
                    $parsed[] = $current;
                }
                break;
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
        if ($last) fwrite($this->socket, chr(0));
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
        if (($c & 0x80) == 0x00) return $c;
        elseif (($c & 0xC0) == 0x80) return (($c & ~0xC0) << 8) + ord(fread($this->socket, 1));
        elseif (($c & 0xE0) == 0xC0) return (($c & ~0xE0) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        elseif (($c & 0xF0) == 0xE0) return (($c & ~0xF0) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        elseif ($c == 0xF0) return (ord(fread($this->socket, 1)) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        return 0;
    }

    public function debugPrint($data) {
        echo "<pre>";
        print_r($data);
        echo "</pre>";
    }

    // Future enhancement placeholders
    public function addUser($username, $password, $profile) {}
    public function topupUser($username, $profile) {}
    public function disconnectUser($username) {}
    public function disableUser($username) {}
    public function getUserUptime($username) { return '1h5m'; }
}