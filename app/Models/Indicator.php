<?php

namespace App\Models;

/**
 * Indicator Model (Alias)
 *
 * This is a thin alias that extends the canonical Objective model.
 * Both "Indicator" and "Objective" point to the same `objectives` table
 * and share the same logic.
 *
 * WHY THIS EXISTS:
 * ────────────────
 * The codebase originally had two nearly-identical 950-line models
 * (Indicator.php and Objective.php) both pointing to the `objectives` table.
 * Rather than do a risky search-and-replace of every `Indicator` reference
 * in 20+ files, we keep this thin alias so all existing code that imports
 * `App\Models\Indicator` continues to work perfectly.
 *
 * NEW CODE SHOULD USE: App\Models\Objective
 *
 * @see \App\Models\Objective The canonical model with all logic.
 */
class Indicator extends Objective
{
    // All logic lives in Objective. This class inherits everything.
    // It exists purely for backward compatibility with existing imports:
    //   use App\Models\Indicator;
    //   use App\Models\Indicator as Objective;
}
