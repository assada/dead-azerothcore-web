<?php

namespace App\Support;

use App\Exceptions\WorldCommandFailed;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class WorldConsole
{
    public function execute(int $realmId, string $command): void
    {
        $config = config("wow.realms.$realmId.soap");
        if (! $config || ! $config['url'] || ! $config['username'] || ! $config['password']) {
            throw new WorldCommandFailed('Character services are unavailable. Please try again later.');
        }

        $command = htmlspecialchars($command, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $body = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="urn:AC">'
            .'<SOAP-ENV:Body><ns1:executeCommand><command>'.$command.'</command></ns1:executeCommand></SOAP-ENV:Body></SOAP-ENV:Envelope>';

        try {
            $response = Http::withBasicAuth($config['username'], $config['password'])
                ->connectTimeout(3)->timeout(8)->withoutRedirecting()
                ->withBody($body, 'text/xml; charset=utf-8')->post($config['url']);
        } catch (ConnectionException) {
            throw new WorldCommandFailed('The server did not confirm the result. Contact an administrator before trying again.', true);
        }

        if (in_array($response->status(), [401, 403], true)) {
            throw new WorldCommandFailed('Character services are unavailable. Please try again later.');
        }

        $xml = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $loaded = $response->body() !== '' && $xml->loadXML($response->body(), LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        if ($loaded && ! $xml->doctype) {
            $xpath = new DOMXPath($xml);
            $xpath->registerNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
            $xpath->registerNamespace('ac', 'urn:AC');
            if ($xpath->query('/soap:Envelope/soap:Body/soap:Fault')->length) {
                throw new WorldCommandFailed('The server could not complete this action. Please try again later.');
            }
            if ($response->successful() && $xpath->query('/soap:Envelope/soap:Body/ac:executeCommandResponse')->length) {
                return;
            }
        }

        throw new WorldCommandFailed('The server did not confirm the result. Contact an administrator before trying again.', true);
    }
}
