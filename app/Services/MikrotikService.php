<?php

namespace App\Services;

use RouterOS\Client;
use RouterOS\Config;
use RouterOS\Query;
use Illuminate\Support\Facades\Log;

class MikrotikService
{
    private ?Client $client = null;

    private function getClient(): Client
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $config = new Config([
            'host' => config('mikrotik.host'),
            'port' => config('mikrotik.port'),
            'user' => config('mikrotik.username'),
            'pass' => config('mikrotik.password'),
            'ssl' => config('mikrotik.ssl'),
        ]);

        return $this->client = new Client($config);
    }

    public function blockUser(string $mac): array
    {
        try {
            $query = (new Query('/ip/hotspot/ip-binding/add'))->equal('mac-address', strtoupper($mac))->equal('type', 'blocked');

            $this->getClient()->query($query)->read();

            return ['success' => true,];
        } catch (\Throwable $e) {
            Log::channel('mikrotik')->error(
                'MikroTik blockUser failed.',
                [
                    'mac' => $mac,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );
            return ['success' => false, 'statuscode' => 503, 'message' => 'MikroTik router is currently unavailable.',];
        }
    }
    public function unblockUser(string $mac): array
    {
        try {
            $query = (new Query('/ip/hotspot/ip-binding/print'))->where('mac-address', strtoupper($mac))->where('type', 'blocked');

            $bindings = $this->getClient()->query($query)->read();

            $binding = $bindings[0] ?? null;

            if (!$binding || !isset($binding['.id'])) {
                return ['success' => false, 'statuscode' => 404, 'message' => 'Blocked user was not found on MikroTik.',];
            }

            $removeQuery = (new Query('/ip/hotspot/ip-binding/remove'))->equal('.id', $binding['.id']);

            $this->getClient()->query($removeQuery)->read();

            return ['success' => true,];
        } catch (\Throwable $e) {
            Log::channel('mikrotik')->error(
                'MikroTik unblockUser failed.',
                [
                    'mac' => $mac,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return ['success' => false, 'statuscode' => 503, 'message' => 'MikroTik router is currently unavailable.',];
        }
    }

    public function terminateSession(string $mac): array
    {
        try {
            $query = (new Query('/ip/hotspot/active/print'))->where('mac-address', strtoupper($mac));

            $sessions = $this->getClient()->query($query)->read();

            if (empty($sessions)) {
                return ['success' => false, 'statuscode' => 404, 'message' => 'No active session found.',];
            }

            $terminated = false;

            foreach ($sessions as $session) {
                if (!isset($session['.id'])) {
                    continue;
                }

                $removeQuery = (new Query('/ip/hotspot/active/remove'))->equal('.id', $session['.id']);

                $this->getClient()->query($removeQuery)->read();

                $terminated = true;
            }

            if (!$terminated) {
                return ['success' => false, 'statuscode' => 502, 'message' => 'Unable to terminate the MikroTik session.',];
            }

            return ['success' => true,];
        } catch (\Throwable $e) {
            Log::channel('mikrotik')->error(
                'MikroTik terminateSession failed.',
                [
                    'mac' => $mac,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );
            return ['success' => false, 'statuscode' => 503, 'message' => 'MikroTik router is currently unavailable.',];
        }
    }

    public function bypassUser(string $mac): array
    {
        try {
            $query = (new Query('/ip/hotspot/ip-binding/add'))->equal('mac-address', strtoupper($mac))->equal('type', 'bypassed');

            $this->getClient()->query($query)->read();

            return ['success' => true,];
        } catch (\Throwable $e) {
            Log::channel('mikrotik')->error(
                'MikroTik bypassUser failed.',
                [
                    'mac' => $mac,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );
            return ['success' => false, 'statuscode' => 503, 'message' => 'MikroTik router is currently unavailable.',];
        }
    }

    public function removeBypassUser(string $mac): array
    {
        try {
            $query = (new Query('/ip/hotspot/ip-binding/print'))
                ->where('mac-address', strtoupper($mac))
                ->where('type', 'bypassed');

            $bindings = $this->getClient()->query($query)->read();

            $binding = $bindings[0] ?? null;

            if (!$binding || !isset($binding['.id'])) {
                return ['success' => false, 'statuscode' => 404, 'message' => 'Permanent access was not found on MikroTik.',];
            }

            $removeQuery = (new Query('/ip/hotspot/ip-binding/remove'))->equal('.id', $binding['.id']);

            $this->getClient()->query($removeQuery)->read();

            return ['success' => true,];
        } catch (\Throwable $e) {
            Log::channel('mikrotik')->error(
                'MikroTik removeBypassUser failed.',
                [
                    'mac' => $mac,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return ['success' => false, 'statuscode' => 503, 'message' => 'MikroTik router is currently unavailable.',];
        }
    }
    public function getIpBindings(): array
    {
        try {
            $query = (new Query('/ip/hotspot/ip-binding/print'))->where('type', 'blocked');

            $blockedBindings = $this->getClient()->query($query)->read();

            $query = (new Query('/ip/hotspot/ip-binding/print'))->where('type', 'bypassed');

            $bypassedBindings = $this->getClient()->query($query)->read();

            return [
                'success' => true,
                'bindings' => array_merge(
                    $blockedBindings,
                    $bypassedBindings
                ),
            ];
        } catch (\Throwable $e) {
            Log::channel('mikrotik')->error(
                'MikroTik getIpBindings failed.',
                [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return ['success' => false,];
        }
    }
}