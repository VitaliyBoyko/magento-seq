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

## What it does

- mirrors Magento Monolog records to Seq
- exposes a backend instrumentation service for custom events
- exposes a frontend helper as `window.devSeq`
- provides admin config for Seq URL and optional password / API key

## Package name

```text
vitaliyboyko/magento-seq
```

## Suggested install from a sibling directory

Add a path repository to the Magento project:

```json
{
  "repositories": {
    "vitaliyboyko-magento-seq": {
      "type": "path",
      "url": "../seq-docker",
      "options": {
        "symlink": true
      }
    }
  }
}
```

Then require it as a dev dependency:

```bash
composer require --dev vitaliyboyko/magento-seq:@dev
bin/magento module:enable VitaliiBoiko_Seq
bin/magento setup:upgrade
bin/magento cache:flush
```

## Admin config

After install, configure the module in:

`Stores -> Configuration -> Advanced -> Seq (Local Dev)`

Fields:

- `Enabled`
- `Seq URL`
- `Password / API Key`

If the password field is filled, the module sends it as an `X-Seq-ApiKey` header.

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

Frontend events are posted back to Magento and relayed server-side to Seq, so the Seq URL and password do not need to be exposed to the browser.

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

With that container running, a typical local Magento config value for `Seq URL` is:

```text
http://host.docker.internal:5341/api/events/raw?clef
```

If Magento and Seq run in the same Docker Compose network, you can usually use:

```text
http://seq:80/api/events/raw?clef
```
