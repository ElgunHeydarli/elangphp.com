# Recorded development-server baseline

`development-server-2026-10-29.json` is a local experimental result, not a production claim. Both fixtures ran PHP 8.4.24 with CLI OPcache disabled, one worker, concurrency one, 30 warm-up calls per fixture, and three alternating rounds of 300 measured requests. Responses were verified to be identical.

The Elang median was 0.751–0.929 ms per round; the Laravel 13.39.0 median was 6.621–7.035 ms. This describes a minimal stateless JSON route on this shared development host only. System load was not isolated; one Elang round showed visible variability. No inference about database workloads, application feature parity, optimized production runtimes, user capacity, or general superiority is justified.

The recorded PHP allocator memory value is not process RSS or a complete application memory comparison. See ../README.md and the fixture source to reproduce or improve the measurement design.
