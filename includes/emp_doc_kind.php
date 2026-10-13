<?php
// Icon + color for an employee document tile (profile.php "My files", view_employee.php Documents tab).
// Matched on the document type name first (Passport, Iqama, Contract...), then on the file extension.
if (!function_exists('emp_doc_kind')) {
    /** @return array [fa-icon, hex color] */
    function emp_doc_kind($docuType, $ext)
    {
        $byName = [
            'passport' => ['fa-passport', '#2563eb'], 'iqama' => ['fa-id-card', '#0d9488'], 'national id' => ['fa-id-card', '#0d9488'],
            'visa' => ['fa-stamp', '#7c3aed'], 'contract' => ['fa-file-contract', '#7c3aed'], 'certificate' => ['fa-graduation-cap', '#ca8a04'],
            'degree' => ['fa-graduation-cap', '#ca8a04'], 'licen' => ['fa-id-badge', '#ea580c'], 'medical' => ['fa-notes-medical', '#dc2626'],
            'insurance' => ['fa-notes-medical', '#dc2626'], 'bank' => ['fa-building-columns', '#16a34a'], 'iban' => ['fa-building-columns', '#16a34a'],
            'photo' => ['fa-camera', '#0891b2'], 'cv' => ['fa-file-user', '#4f46e5'], 'resume' => ['fa-file-user', '#4f46e5'],
            'end of service' => ['fa-file-signature', '#be123c'], 'eos' => ['fa-file-signature', '#be123c'],
        ];
        foreach ($byName as $kw => $kind) {
            if (stripos((string)$docuType, $kw) !== false) {
                return $kind;
            }
        }
        $byExt = [
            'pdf' => ['fa-file-pdf', '#dc2626'], 'jpg' => ['fa-file-image', '#0284c7'], 'jpeg' => ['fa-file-image', '#0284c7'],
            'png' => ['fa-file-image', '#0284c7'], 'gif' => ['fa-file-image', '#0284c7'], 'heic' => ['fa-file-image', '#0284c7'],
            'doc' => ['fa-file-word', '#2563eb'], 'docx' => ['fa-file-word', '#2563eb'], 'xls' => ['fa-file-excel', '#16a34a'],
            'xlsx' => ['fa-file-excel', '#16a34a'], 'zip' => ['fa-file-zipper', '#ca8a04'], 'rar' => ['fa-file-zipper', '#ca8a04'],
            'txt' => ['fa-file-lines', '#64748b'],
        ];
        return $byExt[strtolower((string)$ext)] ?? ['fa-file-lines', '#64748b'];
    }
}
