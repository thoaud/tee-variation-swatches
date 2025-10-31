## Cache invalidation improvements

- **Version-based keys for variation stock/images (fast writes, no blocking)**
  - Store a per-product version: `version_{product_id}` and include it in keys: `variation_stock_{product_id}_v{version}`, `variation_images_{product_id}_v{version}`.
  - On stock/image updates, increment the version instead of deleting cache. Old entries expire naturally via TTL.
  - Benefits: avoids synchronous `wp_cache_delete()` on writes, prevents thundering herd, improves scalability.

- **Lazy invalidation for term meta (lower change frequency)**
  - On term edit, set a lightweight “stale” flag (timestamp/boolean) per term.
  - On read, if stale, rebuild and overwrite the cached `term_meta_{term_id}` entry.
  - Benefits: keeps writes fast, only pays rebuild cost when data is actually read.

- **Background cleanup for bulk operations (optional)**
  - When many products/variations update, enqueue background jobs (Action Scheduler) to clean old keys or pre-warm hot caches.
  - Benefits: prevents admin/UI requests from blocking during large updates, allows batching and rate limiting of cache work.