<?php
/**
 * Minimal ZKTeco TCP/IP protocol client - CONNECT/EXIT handshake only.
 *
 * Scope is deliberately narrow: this only proves a device is reachable and
 * speaking the ZK protocol (the same handshake ZKTeco's own SDK/software
 * does first), used purely as a live online/offline probe. It does NOT
 * attempt to read attendance logs over this socket - that requires parsing
 * a binary record format that differs by firmware/model and can't be
 * verified without a real device to test against; getting it wrong would
 * silently corrupt punch data. Punch data is sourced from BioTime's own
 * (already-proven) database instead - see zk_socket_sync.php.
 *
 * Protocol reference: the widely-documented ZKTeco TCP/IP protocol used by
 * tools like pyzk/zklib (packet: 8-byte header [command, checksum, session_id,
 * reply_id] as little-endian uint16s, wrapped for TCP transport with a
 * 4-byte magic 0x5050827D + 4-byte little-endian payload length).
 */
class ZkSocketClient
{
    const CMD_CONNECT = 1000;
    const CMD_EXIT = 1001;
    const CMD_ACK_OK = 2000;
    const CMD_ACK_ERROR = 2001;
    const CMD_ACK_UNAUTH = 2005;

    private $ip;
    private $port;
    private $timeoutSeconds;
    private $socket;
    private $sessionId = 0;
    private $replyId = 0xFFFF;

    public function __construct($ip, $port, $timeoutSeconds = 5)
    {
        $this->ip = $ip;
        $this->port = (int) $port;
        $this->timeoutSeconds = $timeoutSeconds;
    }

    /** Attempts the CONNECT handshake. Returns true only on a real protocol ACK. */
    public function connect()
    {
        $this->socket = @fsockopen($this->ip, $this->port, $errno, $errstr, $this->timeoutSeconds);
        if (!$this->socket) {
            return false;
        }
        stream_set_timeout($this->socket, $this->timeoutSeconds);

        $this->sessionId = 0;
        $this->replyId = 0xFFFF;

        if (!$this->sendCommand(self::CMD_CONNECT)) {
            $this->closeSocket();
            return false;
        }

        $response = $this->readResponse();
        if ($response === null) {
            $this->closeSocket();
            return false;
        }

        if ($response['command'] === self::CMD_ACK_OK) {
            $this->sessionId = $response['session_id'];
            return true;
        }

        // Device requires auth we don't support yet, or rejected us outright.
        $this->closeSocket();
        return false;
    }

    public function disconnect()
    {
        if ($this->socket) {
            $this->sendCommand(self::CMD_EXIT);
        }
        $this->closeSocket();
    }

    private function closeSocket()
    {
        if ($this->socket) {
            @fclose($this->socket);
            $this->socket = null;
        }
    }

    private function sendCommand($command, $data = '')
    {
        if (!$this->socket) {
            return false;
        }

        $this->replyId = ($this->replyId + 1) & 0xFFFF;

        $header = pack('vvvv', $command, 0, $this->sessionId, $this->replyId) . $data;
        $checksum = self::checksum($header);
        $packet = pack('vvvv', $command, $checksum, $this->sessionId, $this->replyId) . $data;

        $wrapped = "\x50\x50\x82\x7d" . pack('V', strlen($packet)) . $packet;
        $written = @fwrite($this->socket, $wrapped);
        return $written !== false;
    }

    private function readResponse()
    {
        $wrapperHeader = $this->readExact(8);
        if ($wrapperHeader === null || substr($wrapperHeader, 0, 4) !== "\x50\x50\x82\x7d") {
            return null;
        }
        $lenData = unpack('Vlen', substr($wrapperHeader, 4, 4));
        $length = $lenData['len'];
        if ($length < 8 || $length > 1048576) {
            return null;
        }

        $packet = $this->readExact($length);
        if ($packet === null) {
            return null;
        }

        $fields = unpack('vcommand/vchecksum/vsession_id/vreply_id', substr($packet, 0, 8));
        $fields['data'] = substr($packet, 8);
        return $fields;
    }

    private function readExact($bytes)
    {
        $buffer = '';
        while (strlen($buffer) < $bytes) {
            $chunk = @fread($this->socket, $bytes - strlen($buffer));
            if ($chunk === false || $chunk === '') {
                $meta = @stream_get_meta_data($this->socket);
                if (!empty($meta['timed_out']) || feof($this->socket)) {
                    return null;
                }
                continue;
            }
            $buffer .= $chunk;
        }
        return $buffer;
    }

    private static function checksum($buf)
    {
        $len = strlen($buf);
        $checksum = 0;
        $i = 0;
        while ($len > 1) {
            $checksum += ord($buf[$i]) | (ord($buf[$i + 1]) << 8);
            if ($checksum > 0xFFFF) {
                $checksum -= 0xFFFF;
            }
            $i += 2;
            $len -= 2;
        }
        if ($len) {
            $checksum += ord($buf[$i]);
        }
        while ($checksum > 0xFFFF) {
            $checksum -= 0xFFFF;
        }
        return (~$checksum) & 0xFFFF;
    }
}
