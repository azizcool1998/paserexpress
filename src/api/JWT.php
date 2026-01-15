<?php

class JWT
{
    private static function b64($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function encode($payload, $secret)
    {
        $header = ["alg" => "HS256", "typ" => "JWT"];

        $segments = [
            self::b64(json_encode($header)),
            self::b64(json_encode($payload))
        ];

        $signing_input = implode('.', $segments);
        $signature = hash_hmac("sha256", $signing_input, $secret, true);

        $segments[] = self::b64($signature);
        return implode('.', $segments);
    }

    public static function decode($jwt, $secret)
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return false;

        [$h, $p, $s] = $parts;

        $header = json_decode(base64_decode(strtr($h, '-_', '+/')), true);
        $payload = json_decode(base64_decode(strtr($p, '-_', '+/')), true);

        $valid = hash_equals(
            hash_hmac("sha256", "$h.$p", $secret, true),
            base64_decode(strtr($s, '-_', '+/'))
        );

        return $valid ? $payload : false;
    }
}
