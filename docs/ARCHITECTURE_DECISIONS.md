# Architecture Decisions and Technical Debt

## Review Date: 2026-07-07
## Reviewer: Architecture Audit
## Scope: All Implemented Domains (Phases 1-3)

---

## 1. Versioned Aggregate Pattern

### Decision
Persona and Scenario follow a **head aggregate + immutable versions** pattern:
- A head table (`personas`, `scenarios`) stores identity, status, and `current_version_id`
- A separate versions table (`persona_versions`, `scenario_versions`) stores immutable snapshots on edit
- `replicateForPersona()` / `replicateForScenario()` handles duplication

EvaluationRubric (Global) uses a **flat versioning** scheme:
- A single `evaluation_rubrics` table with `version_number` column and `is_active` flag
- Each edit creates a new row with incremented `version_number` and deactivates the old
- `groupBy('name')` resolves the current version

### Why Different?
Global Rubrics are administrative metadata with simple version histories. The flat scheme avoids a join for the common case (listing all active rubrics). This was an explicit trade-off.

### Why This Is a Problem
1. **Inconsistency**: Two versioning strategies for similar concepts will confuse new developers.
2. **Maintenance burden**: The `GroupBy('name')` in `GlobalRubricController@index` loads ALL historical rows into memory. With 50+ edits per rubric, this becomes an O(n²/2) memory operation on each page load.
3. **No snapshot integrity**: The flat scheme means the active rubric at session-creation time must be resolved by `is_active` at query time, not by version FK. If an admin deactivates a rubric between session creation and evaluation, the evaluation could use the wrong rubric—unless the snapshot already captured it.

### Recommendation
Refactor Global Rubric to follow the same aggregate pattern: `evaluation_rubrics` (head) + `evaluation_rubric_versions` (versions). This eliminates the `groupBy`, matches Persona/Scenario semantics, and makes snapshot resolution FK-based instead of flag-based.

**Target: Phase 3 completion (before RubricMerger implementation).**

---

## 2. Authorization Bypass in ScenarioRubricRequest

### Current Code
```php
// app/Http/Requests/Hq/UpsertScenarioRubricRequest.php
public function authorize(): bool
{
    return $this->user()->canManageRubrics();
}
```

### Problem
This directly calls `$user->canManageRubrics()` instead of invoking the Policy system via `$this->user()->can('update', $model)`. Every other FormRequest (UpsertScenarioRequest, UpsertGlobalRubricRequest) uses the Policy path.

### Impact
- The RubricPolicy is not the single source of truth for rubric access
- If authorization logic changes (e.g., Branch Supervisor can view scenario rubrics), this hardcoded check must be found and updated manually
- Inconsistent with the project's own convention (doc 03_ARCHITECTURE.md: "Do not scatter role-name comparisons")

### Recommendation
Change to delegate through the ScenarioPolicy, since the scenario rubric is accessed in context of a scenario:
```php
public function authorize(): bool
{
    return $this->user()->can('update', $this->route('scenario'));
}
```

**Target: Before Phase 4.**

### Resolution (2026-07-07)
Fixed in commit `[current]`. `UpsertScenarioRubricRequest::authorize()` now calls `$this->user()->can('update', $this->route('scenario'))`, consistent with all other FormRequests. Sales users remain blocked because `ScenarioPolicy::update()` delegates to `canManageScenarios()`, which returns `false` for Sales. Existing `test_sales_cannot_create_scenario_rubric` and `test_sales_cannot_view_scenario_rubric_page` continue to assert 403.

---

## 3. Duplicate Field Mapping in Controllers

### Current Code
`ScenarioController::store()` and `ScenarioController::update()` contain 20+ lines of identical field mapping:
```php
ScenarioVersion::create([
    'scenario_id' => $scenario->id,
    'version_number' => ...,
    'description' => $request->input('description'),
    'sales_briefing' => $request->input('sales_briefing'),
    // ... 15 more fields
]);
```

### Impact
- Any schema change to `scenario_versions` requires edits in two places
- High risk of copy-paste errors diverging over time
- Makes the controller unnecessarily long (155 lines when it should be ~80)

### Recommendation
Extract a private method `buildVersionData(UpsertScenarioRequest $request): array` in ScenarioController, or better, create a dedicated `ScenarioVersionData` DTO/value object:

```php
private function buildVersionData(UpsertScenarioRequest $request, Scenario $scenario, int $versionNumber, User $user): array
{
    return [
        'scenario_id' => $scenario->id,
        'version_number' => $versionNumber,
        'description' => $request->input('description'),
        // ... all fields
    ];
}
```

_Same pattern applies to PersonaController (not reviewed in detail, but likely identical)._

**Target: Before Phase 4.**

---

## 4. Scenario Duplication Loses Rubric Data

### Current Code
```php
// app/Models/ScenarioVersion.php
public function replicateForScenario(Scenario $newScenario, User $user): self
{
    // ... replicates assignedPersonas
    // BUT does NOT replicate rubricOverrides or scenario evaluation rubric
}
```

### Impact
Duplicating a scenario silently loses:
- All `scenario_rubric_overrides` for each scenario version
- The `evaluation_rubrics` of type `SCENARIO` linked to each scenario version

This means duplicating a well-configured scenario produces a scenario with no rubric configuration.

### Recommendation
Add rubric replication to `replicateForScenario()`:
```php
foreach ($this->rubricOverrides as $override) {
    $override->replicate()->fill([
        'scenario_version_id' => $replica->id,
    ])->save();
}
```
And in `Scenario::duplicate()`, handle scenario rubrics by creating a new EvaluationRubric for the cloned version.

**Target: Before Phase 5 (snapshot creation depends on correct rubric data).**

### Resolution (2026-07-07)
Fixed. `replicateForScenario()` now replicates:
1. `rubricOverrides` (ScenarioRubricOverride records)
2. Scenario-specific `EvaluationRubric` (with all items)

Covered by `test_duplicate_preserves_rubric_overrides` and `test_duplicate_preserves_scenario_rubric`.

---

## 5. Missing Database Indexes

| Table | Column(s) | Reason |
|-------|-----------|--------|
| `evaluation_rubrics` | `type` | Used in WHERE clauses |
| `evaluation_rubrics` | `is_active` | Used in WHERE clauses |
| `evaluation_rubrics` | `name` | Used in GROUP BY and ORDER BY |

### Impact
With hundreds of rubric versions, these queries will do full table scans.

### Recommendation
Add indexes in the next migration.

**Target: Before Phase 4 (when salience compiler may query rubrics for merging).**

---

## 6. Coupling Between Domains

### Current State
- **Scenario ⇄ Persona**: Through `scenario_personas` pivot. Acceptable coupling—a scenario must know which personas it references.
- **Scenario ⇄ EvaluationRubric**: Through `scenario_version_id` on evaluation_rubrics and scenario_rubric_overrides. Single-directional dependency; evaluation depends on scenario, not vice versa. Good.
- **User ⇄ All domains**: Through `created_by` foreign keys. Lightweight.
- **Rubric → Scenario**: The `scenario_rubric_overrides.global_rubric_item_key` is a string reference to `evaluation_rubric_items.key`. This is **string-based coupling** rather than FK-based. While the spec explicitly chose this design for cross-version reference, it means:
  - No referential integrity if a rubric item key is renamed
  - The override table cannot cascade on rubric item changes

### Recommendation
Document this as intentional, but add a migration check or validation rule that prevents renaming a global rubric item key if overrides exist that reference it.

**Target: Before Phase 11 (Rubric Merger).**

---

## 7. Redundant Methods on User Model

### Current Code
```php
// app/Models/User.php
public function isSuperAdmin(): bool { ... }
public function isSales(): bool { ... }
```

### Problem
These raw role-check methods exist alongside the `can*()` delegation methods. The Policies exclusively use the `can*()` methods, so `isSuperAdmin()` and `isSales()` are only called from:
1. Blade views/components
2. AuthorizationTest assertions

### Impact
- Two paths to determine the same thing
- A new developer might use `$user->isSuperAdmin()` in a Policy instead of `$user->canManage*()`, defeating the abstraction

### Recommendation
Keep them for Blade convenience but add a docblock note indicating they are view-layer helpers only, not for authorization logic. Alternatively, remove them entirely and use `$user->role === UserRole::SuperAdmin` in the rare cases where it's needed.

**Target: Low priority. Can be deferred indefinitely.**

---

## 8. Global RubricController Performance

### Current Code
```php
EvaluationRubric::with('items', 'createdBy')
    ->where('type', EvaluationRubric::TYPE_GLOBAL)
    ->orderBy('name')
    ->orderBy('version_number', 'desc')
    ->get()
    ->groupBy('name')
    ->map(fn ($group) => $group->first());
```

### Performance Issue
For N rubric names with an average of V versions each:
- Memory: O(N × V) rows loaded into PHP
- Query: Single query returns all rows, PHP does in-memory grouping
- With 20 names × 10 versions = 200 rows, this is fine
- With 50 names × 50 versions = 2500 rows, memory starts to matter

### Recommendation
Use a database-level latest-per-group pattern:
```php
EvaluationRubric::where('type', EvaluationRubric::TYPE_GLOBAL)
    ->whereIn('id', function ($query) {
        $query->selectRaw('MAX(id)')
            ->from('evaluation_rubrics')
            ->where('type', 'GLOBAL')
            ->groupBy('name');
    })
    ->with('items', 'createdBy')
    ->orderBy('name')
    ->get();
```
Or, if refactoring to aggregate pattern (see #1), this becomes a simple query on the head table.

**Target: When Global Rubric performance is observed to be a problem, or as part of the aggregate refactor.**

---

## 9. Builder Service Pattern Assessment

### Current State
Two BuilderServices exist:
- `ScenarioBuilderService`: 9 methods, mostly `parseCommaList()` wrappers + `syncAssignedPersonas()`
- `RubricBuilderService`: 4 methods, delete-all-then-create pattern

### Good
- Controllers stay thin
- Builder methods are individually testable
- Delete-all-then-create is appropriate for admin forms where the user edits the entire set

### Could Be Better
- `ScenarioBuilderService.buildDifficultyConfig()` has inline array filtering that duplicates the `array_filter` pattern already in `parseCommaList()`
- The builder methods accept `Request` directly rather than DTOs, coupling them to HTTP concern
- No interface/contract between BuilderServices

### Recommendation
Consider extracting a `VersionBuilder` abstract base class if a third aggregate emerges. For now, the current pattern is acceptable for two domains.

**Target: If a fourth versioned aggregate is added, generalize into a trait/base class.**

---

## 10. Snapshot-Readiness Assessment

### Status

| Snapshot Type | Data Source | Ready? |
|---------------|-------------|--------|
| Persona Snapshot | `persona_versions` + `persona_objections` + `persona_hidden_information` | Yes. All version-bound, immutable. |
| Scenario Snapshot | `scenario_versions` + `scenario_personas` + `scenario_rubric_overrides` | Yes. All version-bound, immutable. |
| Difficulty Snapshot | `scenario_versions.difficulty_config_json` + `difficulty_level` | Yes. Stored as JSON on the immutable version. |
| Rubric Snapshot | Global Rubric + Scenario Rubric + Overrides | **No.** The `rubric_snapshot_json` will need to be explicitly built at session-creation time because Global Rubric can be deactivated after session creation. |
| Salience Snapshot | `PersonaSalienceCompiler` output | **No.** Compiler not yet implemented (Phase 4). |
| Director Config Snapshot | State sensitivity + allowed modes + difficulty modifiers | **Partial.** Sources exist but need to be collected into a single DTO. |

### Gap
The biggest gap is the **Rubric Snapshot**. If Global Rubric is refactored to the aggregate pattern (see #1), the snapshot can simply store the `evaluation_rubric_version_id`. If not, the `rubric_snapshot_json` must be eagerly materialized.

**Target for Rubric Snapshot: Before Phase 7 (session creation).**

---

## Summary

### Fixed (2026-07-07)
| # | Severity | Item | Resolution |
|---|----------|------|------------|
| 4 | **Critical** | Scenario duplication loses rubric data | `replicateForScenario()` now copies rubricOverrides + EvaluationRubric |
| 2 | **High** | UpsertScenarioRubricRequest bypasses Policy | Uses `can('update', $scenario)` via ScenarioPolicy |

### Must Fix Before Phase 4
| # | Severity | Item |
|---|----------|------|
| 1 | **High** | Global Rubric inconsistent versioning strategy |

### Should Fix Before Phase 7 (Session Creation)
| # | Severity | Item |
|---|----------|------|
| 3 | **Medium** | Duplicate field mapping in controllers |
| 5 | **Medium** | Missing database indexes on evaluation_rubrics |
| 6 | **Low** | String-based override coupling (document only) |
| 8 | **Low** | Global Rubric index performance |

### Can Defer
| # | Severity | Item |
|---|----------|------|
| 7 | **Low** | Redundant isSuperAdmin/isSales methods |
| 9 | **Low** | Builder pattern generalization |
| 10 | **Low** | Snapshot readiness (gaps known, planned phases exist) |

---

## Appendix: Versioning Strategy Comparison

| Aspect | Persona / Scenario | Global Rubric (current) |
|--------|-------------------|------------------------|
| Head table | `personas`, `scenarios` | — |
| Version table | `persona_versions`, `scenario_versions` | `evaluation_rubrics` (same table) |
| Current version resolution | `current_version_id` FK | `is_active = true` + `groupBy('name')` |
| Duplication | `replicateForPersona/scenario` copies to new head + new version | Manual `create()` with incremented `version_number` |
| Immutability | Enforced by separate table | Enforced by deactivating old row |
| FK to session snapshot | `scenario_version_id`, `persona_version_id` | No direct FK — must snapshot JSON |
