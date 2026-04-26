<?php

namespace Tests\Unit\Config;

use Tests\TestCase;

class IamConfigTest extends TestCase
{
    public function test_iam_config_structure()
    {
        // Test password configuration
        $this->assertIsArray(config('iam.password'));
        $this->assertEquals(90, config('iam.password.expiry_days'));
        $this->assertEquals(5, config('iam.password.history_count'));
        $this->assertEquals(8, config('iam.password.min_length'));
        $this->assertTrue(config('iam.password.require_uppercase'));
        $this->assertTrue(config('iam.password.require_lowercase'));
        $this->assertTrue(config('iam.password.require_numbers'));
        $this->assertTrue(config('iam.password.require_symbols'));

        // Test authentication configuration
        $this->assertIsArray(config('iam.authentication'));
        $this->assertIsArray(config('iam.authentication.throttle'));
        $this->assertEquals(900, config('iam.authentication.throttle.window_seconds'));
        $this->assertEquals(5, config('iam.authentication.throttle.max_attempts'));
        $this->assertEquals(10, config('iam.authentication.throttle.lock_attempts'));
        $this->assertEquals(1800, config('iam.authentication.throttle.lock_seconds'));

        // Test MFA configuration
        $this->assertIsArray(config('iam.authentication.mfa'));
        $this->assertTrue(config('iam.authentication.mfa.enabled'));
        $this->assertTrue(config('iam.authentication.mfa.enforce_for_admins'));
        $this->assertEquals(10, config('iam.authentication.mfa.recovery_codes_count'));

        // Test JWT configuration
        $this->assertIsArray(config('iam.jwt'));
        $this->assertEquals(15, config('iam.jwt.access_ttl'));
        $this->assertEquals(43200, config('iam.jwt.refresh_ttl'));
        $this->assertEquals(60, config('iam.jwt.leeway'));
        $this->assertEquals(10, config('iam.jwt.blacklist_grace_period'));
        $this->assertEquals('HS256', config('iam.jwt.algorithm'));
        $this->assertTrue(config('iam.jwt.blacklist_enabled'));

        // Test session configuration
        $this->assertIsArray(config('iam.session'));
        $this->assertEquals(5, config('iam.session.concurrent_limit'));
        $this->assertTrue(config('iam.session.device_tracking'));
        $this->assertFalse(config('iam.session.ip_validation'));
        $this->assertTrue(config('iam.session.user_agent_validation'));

        // Test security configuration
        $this->assertIsArray(config('iam.security'));
        $this->assertTrue(config('iam.security.activity_logging'));
        $this->assertTrue(config('iam.security.failed_login_logging'));
        $this->assertEquals(10, config('iam.security.suspicious_activity_threshold'));
        $this->assertTrue(config('iam.security.auto_lock_suspicious_accounts'));

        // Test organization configuration
        $this->assertIsArray(config('iam.organization'));
        $this->assertTrue(config('iam.organization.require_context'));
        $this->assertEquals('member', config('iam.organization.default_role'));
        $this->assertEquals(1000, config('iam.organization.max_members'));
        $this->assertEquals(72, config('iam.organization.invitation_expiry_hours'));
    }

    public function test_config_values_are_used_correctly()
    {
        // Test that JWT config matches iam config
        $this->assertEquals(config('iam.jwt.access_ttl'), config('jwt.ttl'));
        $this->assertEquals(config('iam.jwt.refresh_ttl'), config('jwt.refresh_ttl'));
        $this->assertEquals(config('iam.jwt.leeway'), config('jwt.leeway'));
        $this->assertEquals(config('iam.jwt.blacklist_grace_period'), config('jwt.blacklist_grace_period'));
        $this->assertEquals(config('iam.jwt.algorithm'), config('jwt.algo'));
        $this->assertEquals(config('iam.jwt.blacklist_enabled'), config('jwt.blacklist_enabled'));
    }
}
