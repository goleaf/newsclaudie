<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-Based Tests for Comment IP Address Masking
 * 
 * These tests verify universal properties for IP address privacy masking.
 * 
 * @group property-testing
 * @group comment-model
 * @group privacy
 */
final class CommentIpMaskingPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property: IPv4 addresses should be masked with 'xxx' in last octet
     * Validates: Privacy compliance for IPv4
     */
    public function test_masks_ipv4_addresses_correctly(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $testCases = [
            '192.168.1.100' => '192.168.1.xxx',
            '10.0.0.1' => '10.0.0.xxx',
            '172.16.254.1' => '172.16.254.xxx',
            '8.8.8.8' => '8.8.8.xxx',
            '255.255.255.255' => '255.255.255.xxx',
        ];
        
        foreach ($testCases as $original => $expected) {
            $comment = Comment::factory()->for($user)->for($post)->create([
                'content' => 'Test comment',
                'ip_address' => $original,
            ]);
            
            // Property: Masked IP should match expected format
            $this->assertEquals($expected, $comment->masked_ip, "IPv4 {$original} should be masked as {$expected}");
            
            // Property: Original IP should remain unchanged
            $this->assertEquals($original, $comment->ip_address, "Original IP should remain unchanged");
        }
    }

    /**
     * Property: IPv6 addresses should be masked with 'xxxx' in last segment
     * Validates: Privacy compliance for IPv6
     */
    public function test_masks_ipv6_addresses_correctly(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $testCases = [
            '2001:0db8:85a3:0000:0000:8a2e:0370:7334' => '2001:0db8:85a3:0000:0000:8a2e:0370:xxxx',
            '2001:db8::1' => '2001:db8::xxxx',
            'fe80::1' => 'fe80::xxxx',
            '::1' => '::xxxx',
        ];
        
        foreach ($testCases as $original => $expected) {
            $comment = Comment::factory()->for($user)->for($post)->create([
                'content' => 'Test comment',
                'ip_address' => $original,
            ]);
            
            // Property: Masked IP should match expected format
            $this->assertEquals($expected, $comment->masked_ip, "IPv6 {$original} should be masked as {$expected}");
            
            // Property: Original IP should remain unchanged
            $this->assertEquals($original, $comment->ip_address, "Original IP should remain unchanged");
        }
    }

    /**
     * Property: Null IP addresses should return null when masked
     * Validates: Null handling
     */
    public function test_handles_null_ip_address_gracefully(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $comment = Comment::factory()->for($user)->for($post)->create([
            'content' => 'Test comment',
            'ip_address' => null,
        ]);
        
        // Property: Null IP should return null when masked
        $this->assertNull($comment->masked_ip, 'Null IP address should return null when masked');
    }

    /**
     * Property: Masked IP should preserve network portion for analysis
     * Validates: Usefulness for spam detection while maintaining privacy
     */
    public function test_masked_ip_preserves_network_portion(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        // Create multiple comments from same network
        $comment1 = Comment::factory()->for($user)->for($post)->create([
            'content' => 'Comment 1',
            'ip_address' => '192.168.1.100',
        ]);
        
        $comment2 = Comment::factory()->for($user)->for($post)->create([
            'content' => 'Comment 2',
            'ip_address' => '192.168.1.200',
        ]);
        
        // Property: Both should have same network portion in masked IP
        $this->assertStringStartsWith('192.168.1.', $comment1->masked_ip, 'Masked IP should preserve network portion');
        $this->assertStringStartsWith('192.168.1.', $comment2->masked_ip, 'Masked IP should preserve network portion');
        
        // Property: Both should end with 'xxx'
        $this->assertStringEndsWith('xxx', $comment1->masked_ip, 'Masked IP should end with xxx');
        $this->assertStringEndsWith('xxx', $comment2->masked_ip, 'Masked IP should end with xxx');
    }

    /**
     * Property: Accessor should not modify database value
     * Validates: Read-only accessor behavior
     */
    public function test_masked_ip_accessor_does_not_modify_database(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $originalIp = '192.168.1.100';
        $comment = Comment::factory()->for($user)->for($post)->create([
            'content' => 'Test comment',
            'ip_address' => $originalIp,
        ]);
        
        // Access masked IP
        $maskedIp = $comment->masked_ip;
        
        // Property: Database value should remain unchanged
        $this->assertEquals($originalIp, $comment->fresh()->ip_address, 'Accessing masked_ip should not modify database value');
        
        // Property: Masked IP should be different from original
        $this->assertNotEquals($originalIp, $maskedIp, 'Masked IP should be different from original');
    }

    /**
     * Property: Multiple accesses should return consistent result
     * Validates: Accessor consistency
     */
    public function test_masked_ip_accessor_is_consistent(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $comment = Comment::factory()->for($user)->for($post)->create([
            'content' => 'Test comment',
            'ip_address' => '192.168.1.100',
        ]);
        
        // Property: Multiple accesses should return same value
        $masked1 = $comment->masked_ip;
        $masked2 = $comment->masked_ip;
        $masked3 = $comment->masked_ip;
        
        $this->assertEquals($masked1, $masked2, 'Multiple accesses should return consistent result');
        $this->assertEquals($masked2, $masked3, 'Multiple accesses should return consistent result');
    }
}

