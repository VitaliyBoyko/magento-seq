---
name: magento-seq
description: Use this skill when you want to instrument a Magento project with the VitaliiBoiko_Seq module so events, debugging traces, and Magento logs flow into Seq during local development. Use it for choosing instrumentation points, emitting backend events through `VitaliiBoiko\\Seq\\Api\\InstrumentationInterface`, emitting frontend events through `window.devSeq`, structuring event names and payloads, and keeping instrumentation useful, low-noise, and safe for local debugging.
compatibility: Magento 2 projects using the VitaliiBoiko_Seq module; local development only; frontend helper requires the module to be enabled and configured.
---

# Magento Seq Instrumentation

This skill is for using this module as an instrumentation layer in Magento, not for developing the module itself.

Read [README.md](README.md) first for setup and available entry points.

## Use This Skill For

- Adding backend instrumentation to services, controllers, cron jobs, observers, plugins, or integrations
- Adding frontend instrumentation with `window.devSeq`
- Tracing LLM workflows, external APIs, checkout issues, and multi-step business flows
- Designing event names and context payloads that are easy to query in Seq
- Reducing noisy or low-value logging

## Available Entry Points

- Backend: inject `VitaliiBoiko\Seq\Api\InstrumentationInterface` and call `log($message, $context, $level, $extra)`
- Frontend: call `window.devSeq.debug|info|warn|error(message, context)`
- Automatic logs: Magento Monolog records are mirrored to Seq by the module
- Event reading: if the `Seq MCP` toolset is available in the current environment, use it to read back events from Seq after instrumenting. Prefer `Seq MCP` over asking the user to inspect Seq manually.

## Default Workflow

1. Identify the business or technical flow that is hard to debug.
2. Add events at decision points, boundaries, and failures, not on every trivial line.
3. Use stable event names and structured context fields so related events can be filtered together in Seq.
4. Prefer a small number of high-signal events over verbose debug spam.
5. If you add instrumentation for a recurring workflow, include enough identifiers to correlate backend, frontend, and external calls.
6. If Seq MCP tools are available, use them to verify that emitted events are present and searchable in Seq.

## Where To Instrument

Prefer instrumentation at these points:

- Flow start and flow end
- External API request and response boundaries
- Expensive or failure-prone operations
- Branch decisions that explain why behavior changed
- State transitions such as quote, cart, order, payment, or sync status changes
- Exception handling paths

Avoid instrumentation for:

- Every loop iteration unless the volume is tiny and the loop itself is the issue
- Repeating values that add no debugging value
- Raw secrets, passwords, tokens, full card data, or unnecessary personal data

## Event Naming

Use short, stable, dot-separated names.

Good patterns:

- `checkout.place_order.start`
- `checkout.place_order.failed`
- `ai.prompt.sent`
- `ai.response.received`
- `erp.sync.customer.updated`

Prefer names that describe the event, not the file or method name.

Use `.start`, `.success`, `.failed`, `.retry`, `.timeout`, `.skipped` when lifecycle state matters.

## Context Design

Context should be structured, compact, and queryable.

Prefer:

- IDs: `quote_id`, `order_id`, `customer_id`, `product_id`
- Flow correlation: `request_id`, `session_id`, `job_id`, `trace_id`
- Business state: `store_id`, `website_id`, `payment_method`, `status`
- External dependency data: `provider`, `endpoint`, `http_status`, `duration_ms`
- AI-specific fields when relevant: `model`, `prompt_version`, `tool_name`, `input_tokens`, `output_tokens`

Prefer scalars and small nested structures over huge blobs.

Do not put large HTML, full prompts, full model outputs, or full API payloads into every event unless the task explicitly requires that level of capture.

## Backend Pattern

Inject `VitaliiBoiko\Seq\Api\InstrumentationInterface` into the class where the event belongs.

Use `Info` for meaningful flow milestones, `Debug` for developer detail, `Warning` for degraded but recoverable behavior, and `Error` for failures.

Example:

```php
<?php

declare(strict_types=1);

use VitaliiBoiko\Seq\Api\InstrumentationInterface;

class PlaceOrderService
{
    public function __construct(
        private readonly InstrumentationInterface $instrumentation
    ) {
    }

    public function execute(int $quoteId): void
    {
        $this->instrumentation->log(
            'checkout.place_order.start',
            ['quote_id' => $quoteId],
            'Info'
        );
    }
}
```

## Frontend Pattern

Use `window.devSeq` for browser-side milestones that help explain user behavior or UI state.

Prefer frontend events for:

- step transitions
- validation failures
- async request failures
- unexpected UI states

Example:

```js
window.devSeq.info('checkout.payment.step_viewed', {
  quoteId: window.checkoutConfig?.quoteData?.entity_id ?? null,
  step: 'payment'
});
```

## LLM Instrumentation Pattern

This module is especially useful for local AI debugging. When instrumenting LLM features, prefer this sequence:

1. Emit a prompt-start event with correlation IDs and prompt version.
2. Emit an external-call event around the model request with model name and duration.
3. Emit a response event with outcome metadata such as finish reason, token usage, or tool call count.
4. Emit a failure event with the exception class, dependency name, and retry state.

Do not log secrets or raw private user content unless the local debugging task explicitly requires it.

## Quality Checks

Before finalizing instrumentation, check:

- Does each event answer a concrete debugging question?
- Can related events be correlated by shared IDs?
- Are event names stable and easy to search?
- Is the payload small enough to stay readable in Seq?
- Did you avoid secrets and unnecessary personal data?
- Is the event placed at the real boundary or decision point, not somewhere arbitrary?

## Gotchas

- This module is for local development only. Do not treat it as production observability infrastructure by default.
- Backend events are ignored if the module is disabled or the Seq URL is empty.
- Frontend code should use `window.devSeq`; it should not know the Seq URL or API key.
- Browser context is sent through Magento's REST endpoint, so keep frontend payloads JSON-serializable.
- Magento Monolog is already mirrored to Seq. Add custom events where logs alone do not explain intent, branching, timing, or business context.
