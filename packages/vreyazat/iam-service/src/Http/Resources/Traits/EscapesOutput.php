<?php

namespace IamApi\Http\Resources\Traits;

use IamApi\Helpers\SecurityHelper;

/**
 * Escapes Output Trait
 * 
 * Automatically escapes string values in API resources to prevent XSS attacks.
 * This provides defense-in-depth even if frontend doesn't properly escape output.
 */
trait EscapesOutput
{
    /**
     * Fields that should NOT be escaped (e.g., already-safe fields, IDs, timestamps).
     * Override this in your resource class if needed.
     * 
     * @var array
     */
    protected array $unescapedFields = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
        'email_verified_at',
        'phone_verified_at',
        'last_login_at',
        'password_expires_at',
        'locked_until',
        'mfa_enabled',
        'is_super_admin',
        'is_locked',
        'must_change_password',
        'status',
        'organization_id',
        'user_id',
        'department_id',
        'role_id',
        'permission_id',
    ];

    /**
     * Fields that contain safe HTML and should use sanitizeHtml instead of full escape.
     * Override this in your resource class for fields that need to preserve HTML.
     * 
     * @var array
     */
    protected array $htmlFields = [
        // Example: 'description', 'content', 'bio'
    ];

    /**
     * Escape the resource data before returning.
     * 
     * @param array $data The resource data
     * @return array The escaped data
     */
    protected function escapeOutput(array $data): array
    {
        return $this->escapeArrayRecursive($data);
    }

    /**
     * Recursively escape array values.
     * 
     * @param array $data The data to escape
     * @return array The escaped data
     */
    protected function escapeArrayRecursive(array $data): array
    {
        $escaped = [];

        foreach ($data as $key => $value) {
            // Skip unescaped fields
            if (in_array($key, $this->unescapedFields, true)) {
                $escaped[$key] = $value;
                continue;
            }

            // Handle arrays recursively
            if (is_array($value)) {
                $escaped[$key] = $this->escapeArrayRecursive($value);
                continue;
            }

            // Handle strings
            if (is_string($value)) {
                // Use sanitizeHtml for HTML fields
                if (in_array($key, $this->htmlFields, true)) {
                    $escaped[$key] = SecurityHelper::sanitizeHtml($value);
                } else {
                    // Full escape for regular text fields
                    $escaped[$key] = SecurityHelper::escapeHtml($value);
                }
                continue;
            }

            // Keep other types as-is (numbers, booleans, null, objects)
            $escaped[$key] = $value;
        }

        return $escaped;
    }

    /**
     * Override toArray to automatically escape output.
     * Child classes should call parent::toArray() or use escapeOutput().
     */
    abstract public function toArray($request): array;
}
