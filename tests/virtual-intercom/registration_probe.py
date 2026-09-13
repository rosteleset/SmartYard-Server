#!/usr/bin/env python3
"""Verify expired-call registration against a local, configured Asterisk.

Creates one temporary VI:AUTH credential without a call/session. Uses loopback
SIP only; never pushes to a resident or invokes a physical door. Run as a user
who can read server/config/config.json. Secrets never appear in the report.
"""
import argparse
import hashlib
import json
import re
import secrets
import socket
import subprocess

from probe import SIP


PHP = r'''
$p = json_decode(stream_get_contents(STDIN), true);
$c = json_decode(file_get_contents($p['root'] . '/server/config/config.json'), true);
$r = new Redis(); $r->connect($c['redis']['host'], $c['redis']['port']);
if (!empty($c['redis']['password'])) $r->auth($c['redis']['password']);
$key = 'VI:AUTH:' . $p['extension'];
if ($p['action'] === 'create') {
    $secret = bin2hex(random_bytes(24));
    if ($r->get('mobile_extension_' . $p['extension']) || !$r->set($key, $secret, ['nx', 'ex' => 120])) exit(2);
    echo json_encode(['password' => $secret]);
} else {
    $r->eval("if redis.call('GET',KEYS[1])==ARGV[1] then return redis.call('DEL',KEYS[1]) end; return 0", [$key, $p['password']], 1);
    echo '{}';
}
'''


def credential(root, extension, action, password=None):
    result = subprocess.run(['php', '-r', PHP], input=json.dumps(dict(
        root=root, extension=extension, action=action, password=password)),
        text=True, capture_output=True)
    if result.returncode:
        raise RuntimeError('Temporary credential operation failed')
    return json.loads(result.stdout)


def digest(challenge, user, password, method, uri):
    fields = dict((key, quoted or plain) for key, quoted, plain in re.findall(
        r'(\w+)=(?:"([^"]*)"|([^,\s]+))', challenge))
    md5 = lambda value: hashlib.md5(value.encode()).hexdigest()
    first = md5(f"{user}:{fields['realm']}:{password}")
    second = md5(f'{method}:{uri}')
    parts = [f'username="{user}"', f'realm="{fields["realm"]}"',
             f'nonce="{fields["nonce"]}"', f'uri="{uri}"', 'algorithm=MD5']
    if 'qop' in fields:
        assert 'auth' in fields['qop'].split(',')
        cnonce = secrets.token_hex(8)
        response = md5(f'{first}:{fields["nonce"]}:00000001:{cnonce}:auth:{second}')
        parts += ['qop=auth', 'nc=00000001', f'cnonce="{cnonce}"']
    else:
        response = md5(f'{first}:{fields["nonce"]}:{second}')
    parts.append(f'response="{response}"')
    if 'opaque' in fields:
        parts.append(f'opaque="{fields["opaque"]}"')
    return 'Digest ' + ', '.join(parts)


def run(root, port):
    # Outside the normal 2000xxxxxx autoextension allocation range.
    extension = '2999' + f'{secrets.randbelow(1000000):06d}'
    password = credential(root, extension, 'create')['password']
    conn = None
    try:
        conn = socket.create_connection(('127.0.0.1', port), timeout=5)
        sip = SIP(conn)
        contact = f'<sip:{extension}@127.0.0.1:{conn.getsockname()[1]};transport=tcp>'

        def request(method, expires=60):
            uri = f'sip:127.0.0.1:{port}' if method == 'REGISTER' else f'sip:virtual-probe-denied@127.0.0.1:{port}'
            identity = f'<sip:{extension}@127.0.0.1>'
            call_id, tag = secrets.token_hex(12), secrets.token_hex(8)
            authorization = None
            for sequence in (1, 2):
                via = f'SIP/2.0/TCP 127.0.0.1:{conn.getsockname()[1]};branch=z9hG4bK{secrets.token_hex(10)};rport'
                headers = [('Via', via), ('Max-Forwards', '5'), ('From', identity + ';tag=' + tag),
                           ('To', identity if method == 'REGISTER' else '<' + uri + '>'),
                           ('Call-ID', call_id), ('CSeq', f'{sequence} {method}'), ('Contact', contact)]
                if authorization:
                    headers.append(('Authorization', authorization))
                body = ''
                if method == 'REGISTER':
                    headers.append(('Expires', str(expires)))
                else:
                    headers.append(('Content-Type', 'application/sdp'))
                    body = 'v=0\r\no=- 1 1 IN IP4 127.0.0.1\r\ns=Isolated registration probe\r\nc=IN IP4 127.0.0.1\r\nt=0 0\r\nm=audio 55166 RTP/AVP 8\r\na=rtpmap:8 PCMA/8000\r\n'
                sip.send(f'{method} {uri} SIP/2.0', headers, body)
                while True:
                    reply = sip.receive(timeout=8)
                    if not reply[0].startswith('SIP/2.0 '):
                        sip.reply(reply)
                        continue
                    code = int(reply[0].split()[1])
                    if code >= 200:
                        break
                response_headers = dict(reply[1])
                if method == 'INVITE' and code >= 300:
                    sip.send(f'ACK {uri} SIP/2.0', [('Via', via), ('Max-Forwards', '5'),
                        ('From', identity + ';tag=' + tag), ('To', response_headers['to']),
                        ('Call-ID', call_id), ('CSeq', f'{sequence} ACK')])
                if code == 401 and sequence == 1:
                    authorization = digest(response_headers['www-authenticate'], extension, password, method, uri)
                    continue
                return code
            raise RuntimeError('SIP authentication did not complete')

        registered = request('REGISTER')
        assert registered == 200, f'Late REGISTER failed ({registered})'
        rejected = request('INVITE')
        assert rejected in (403, 603), f'Resident context did not reject the call ({rejected})'
        unregistered = request('REGISTER', expires=0)
        assert unregistered == 200, f'Unregister failed ({unregistered})'
        print(json.dumps(dict(register=registered, outgoingInvite=rejected,
                              unregister=unregistered, pushes=0, physicalCommands=0)))
    finally:
        if conn:
            conn.close()
        credential(root, extension, 'delete', password)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--run', action='store_true', required=True)
    parser.add_argument('--root', default='/opt/rbt')
    parser.add_argument('--port', type=int, default=50601)
    args = parser.parse_args()
    run(args.root, args.port)
