<?php

namespace Laravilt\Auth\Mcp;

use Laravel\Mcp\Server;
use Laravilt\Auth\Mcp\Tools\GetEventInfoTool;
use Laravilt\Auth\Mcp\Tools\ListAuthMethodsTool;
use Laravilt\Auth\Mcp\Tools\SearchDocsTool;

class LaraviltAuthServer extends Server
{
    protected string $name = 'Laravilt Auth';

    protected string $version = '1.0.0';

    protected string $instructions = <<<'MARKDOWN'
        This server provides authentication capabilities for Laravilt projects.

        You can:
        - Search authentication documentation
        - Get information about authentication events
        - List available authentication methods
        - Access information about login, registration, 2FA, OTP, social auth, passkeys, and magic links

        All 8 authentication methods are fully supported with comprehensive event system.
    MARKDOWN;

    protected array $tools = [
        SearchDocsTool::class,
        GetEventInfoTool::class,
        ListAuthMethodsTool::class,
    ];
}
