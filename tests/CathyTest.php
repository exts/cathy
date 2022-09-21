<?php
namespace Tests;

use Exts\Cathy\Cathy;
use PHPUnit\Framework\TestCase;

final class CathyTest extends TestCase
{
    public function testTokenGenerationValidity()
    {
        $cathy = new Cathy();

        $expected_results = [
            hash(Cathy::HASH_MECHANISM, 'example'),
            hash(Cathy::HASH_MECHANISM, 'example/path'),
            hash(Cathy::HASH_MECHANISM, 'example/path/token'), // last token is always string token
        ];

        $current_token = "example/path/token";
        $tokens = $cathy->generateKeysFromPath($current_token);
        foreach($tokens as $idx => $token) {
            $this->assertEquals($expected_results[$idx], $token);
        }

        return $cathy;
    }

    /**
     * @depends testTokenGenerationValidity
     */
    public function testTokenTreeGenerationValidity(Cathy $cathy)
    {
        $token = "example/path/token";

        // the first key gets ignored since that data is being stored in the
        // cache as an array. Allowing us to keep track of which key groups to delete
        $expected = [
            hash(Cathy::HASH_MECHANISM, 'example/path') => [
                hash(Cathy::HASH_MECHANISM, 'example/path/token') => []
            ]
        ];

        $tree = $cathy->generateKeyTreeFromPath($token);

        $this->assertEquals($expected, $tree);
    }

    /**
     * @depends testTokenGenerationValidity
     */
    public function testTokenTreeAddingValidity(Cathy $cathy)
    {
        $expected = [
            hash(Cathy::HASH_MECHANISM, 'example/path') => [
                hash(Cathy::HASH_MECHANISM, 'example/path/x') => [],
                hash(Cathy::HASH_MECHANISM, 'example/path/y') => [],
                hash(Cathy::HASH_MECHANISM, 'example/path/z') => [],
            ],
        ];

        $key1 = "example/path/x";
        $key2 = "example/path/y";
        $key3 = "example/path/z";

        $tree = $cathy->generateKeyTreeFromPath($key1);
        $tree = $cathy->addKeysToTree($tree, $cathy->generateKeysFromPath($key2));
        $tree = $cathy->addKeysToTree($tree, $cathy->generateKeysFromPath($key3));

        $this->assertEquals($expected, $tree);
    }

    /**
     * @depends testTokenGenerationValidity
     */
    public function testTokenTreeRemovalValidity(Cathy $cathy)
    {
        $key1 = "example/path/x";
        $key2 = "example/path/y";
        $key3 = "example/path/z/long/extra/path";
        $remove_key = "example/path/z/long/extra"; //remove key

        $expected = [
            hash(Cathy::HASH_MECHANISM, 'example/path') => [
                hash(Cathy::HASH_MECHANISM, 'example/path/x') => [],
                hash(Cathy::HASH_MECHANISM, 'example/path/y') => [],
                hash(Cathy::HASH_MECHANISM, 'example/path/z') => [
                    hash(Cathy::HASH_MECHANISM, 'example/path/z/long') => []
                ],
            ],
        ];

        $expected2 = [
            hash(Cathy::HASH_MECHANISM, $remove_key),
            hash(Cathy::HASH_MECHANISM, $remove_key . '/path'),
        ];

        $tree = $cathy->generateKeyTreeFromPath($key1);
        $tree = $cathy->addKeysToTree($tree, $cathy->generateKeysFromPath($key2));
        $tree = $cathy->addKeysToTree($tree, $cathy->generateKeysFromPath($key3));
        $deleted = $cathy->removeKeysFromTree($tree, $cathy->generateKeysFromPath($remove_key));

        $this->assertEquals($expected, $tree);
        $this->assertEquals($expected2, $deleted);

        $deleted2 = $cathy->removeKeysFromTree($tree, $cathy->generateKeysFromPath("example"));

        $expected3 = [
            hash(Cathy::HASH_MECHANISM, 'example/path'),
            hash(Cathy::HASH_MECHANISM, 'example/path/x'),
            hash(Cathy::HASH_MECHANISM, 'example/path/y'),
            hash(Cathy::HASH_MECHANISM, 'example/path/z'),
            hash(Cathy::HASH_MECHANISM, 'example/path/z/long'),
        ];

        $this->assertEquals($expected3, $deleted2);
    }
}