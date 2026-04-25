# Magento Seq

`magento-seq` is a Magento 2 module for local development only.

Do not install this in production. It is meant for developer machines where you want Magento logs and ad hoc instrumentation to flow into Seq without patching the project codebase.

## What is Seq

Seq is a structured log and event server built for fast debugging. Instead of digging through flat text files, you send events with properties and then filter, search, and correlate them in a UI that is much better suited for tracing application behavior.

For local development, that is especially useful because you can inspect request flow, custom instrumentation, and Magento logs in one place with timestamps, levels, and payload fields intact.

## Why Seq is useful for LLM instrumentation

LLM workflows are much easier to debug when prompts, responses, timings, model names, token counts, tool calls, and request context are captured as structured events instead of free-form text logs.

Seq is a good fit for that style of instrumentation because it lets you quickly answer questions like:

- which prompt version caused a bad response
- where latency increased in a multi-step pipeline
- which tool call or API dependency failed
- what input or session context led to a hallucination or malformed output

That makes Seq a practical local observability tool when you are iterating on AI features and need a fast feedback loop during debugging.

<table align="center" style="border-collapse: collapse; width: 100%; text-align: center;">
  <tbody>
    <tr style="background-color: #f9f9f9;">
      <td style="padding: 20px;">
        <h3 style="margin: 10px 0;">Support the Project</h3>
        <p>If this module is useful in your local Magento workflow, consider buying the contributor a coffee:</p>
        <a href="https://buymeacoffee.com/vitalii_b" style="text-decoration: none; color: inherit;">
<pre style="display: inline-block; margin: 10px 0; font-family: monospace;">
    ( (
     ) )
  ........
  |      |]
  \      /
   `----'
 Buy Me a Coffee
</pre>
        </a>
      </td>
    </tr>
  </tbody>
</table>

## What it does

- mirrors Magento Monolog records to Seq
- exposes a backend instrumentation service for custom events
- exposes a frontend helper as `window.devSeq`
- exposes an anonymous REST API endpoint for storefront event collection
- provides admin config for Seq host / URL and optional password / API key

## Good companion plugin for IntelliJ IDEs

If you use JetBrains IDEs, [`Seq MCP`](https://plugins.jetbrains.com/plugin/31358-seq-mcp) is a strong fit to use together with this module.

This Magento module is responsible for getting Magento logs and custom instrumentation into Seq. The JetBrains plugin complements that by exposing Seq-focused tools inside the IDE through JetBrains' built-in MCP server, so an AI assistant connected to your IDE can inspect the Seq data produced by this module while you work on the project.

Together, they make a practical local workflow for Magento AI debugging: Magento emits structured events to Seq, and the IDE-side MCP integration makes those events easier to explore from within the development environment.

## Installation

Install the module:

```bash
composer require --dev vitaliyboyko/magento-seq
```

Then enable it in Magento:

```bash
bin/magento module:enable VitaliiBoiko_Seq
bin/magento setup:upgrade
bin/magento cache:flush
```

## Admin config

After install, configure the module in:

`Stores -> Configuration -> Advanced -> Seq (Local Dev)`

Fields:

- `Enabled`
- `Seq Host / URL`
- `Password / API Key`

If the password field is filled, the module sends it as an `X-Seq-ApiKey` header.

If you enter only the Seq host, the module automatically sends to `/api/events/raw?clef`.

The config field is validated on save. It accepts either a plain Seq host such as `http://seq:80`, or the exact raw CLEF endpoint. Magento also performs a live HTTP check against the Seq server during save and shows an admin error if the server is unreachable.

## Backend usage

Inject `VitaliiBoiko\Seq\Api\InstrumentationInterface` and call `log()`:

```php
<?php

declare(strict_types=1);

use VitaliiBoiko\Seq\Api\InstrumentationInterface;

class Example
{
    public function __construct(
        private readonly InstrumentationInterface $instrumentation
    ) {
    }

    public function execute(): void
    {
        $this->instrumentation->log(
            'custom.backend.event',
            ['quote_id' => 123],
            'Info'
        );
    }
}
```

## Frontend usage

When the module is enabled, `window.devSeq` is available on storefront and admin pages:

```js
window.devSeq.debug('custom.frontend.event', { quoteId: 123 });
window.devSeq.info('custom.frontend.event', { step: 'payment' });
window.devSeq.warn('custom.frontend.event', { state: 'unexpected' });
window.devSeq.error('custom.frontend.event', { reason: 'request failed' });
```

Frontend events are posted to Magento through the REST API and relayed server-side to Seq, so the Seq URL and password do not need to be exposed to the browser.

## Seq container setup

You still need a running Seq instance for the module to send events anywhere.

Example Docker Compose service:

```yaml
seq:
  image: datalust/seq:latest
  restart: unless-stopped
  environment:
    - ACCEPT_EULA=Y
    - SEQ_FIRSTRUN_NOAUTHENTICATION=true
  ports:
    - "5341:80"
    - "5342:5341"
  volumes:
    - seqdata:/data
```

Example volume declaration:

```yaml
volumes:
  seqdata:
```

With that container running, a typical local Magento config value for `Seq Host / URL` is:

```text
http://host.docker.internal:5341
```

If Magento and Seq run in the same Docker Compose network, you can usually use:

```text
http://seq:80
```

You can still provide the full raw endpoint explicitly if you want:

```text
http://seq:80/api/events/raw?clef
```
