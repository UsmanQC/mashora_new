<?php

use App\Models\Speciality;

/**
 * Session "Schedule a session" filter UI.
 *
 * Sub-specialty chips are loaded from the `specialities` table (see pages::patient.schedule-session).
 *
 * @see Speciality
 */
return [
    /** Chip count before “Show more” expands the rest. */
    'subspecialties_collapsed_count' => 7,
];
