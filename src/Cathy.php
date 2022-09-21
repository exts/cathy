<?php
namespace Exts\Cathy;

/**
 * Cathy allows you to implement key generation for storing and managing
 * keys using string paths. example: example/path -> will generate proper
 * keys for both example and example/path then keep track under the hashing
 * mechanism using a key named '-data' allowing us to mass delete keys under
 * a key tree.
 *
 * Implementable using any caching library.
 */
class Cathy
{
    /** @var string */
    const HASH_MECHANISM = "sha1";

    /**
     * @param string $path
     *
     * @return array
     */
    public function generateKeysFromPath(string $path) : array
    {
        $path_pieces = explode("/", $path);

        $hashes = [];
        for($count = 0; $count < count($path_pieces); $count++) {
            $hash = [];
            for($c = 0; $c < $count+1; $c++) {
                $hash[] = $path_pieces[$c];
            }

            // connect the current path so far with separators to get the correct string
            $hash_string = implode("/", $hash);
            $hashes[] = hash(self::HASH_MECHANISM, $hash_string);
        }

        return $hashes;
    }

    /**
     * @param string $path
     *
     * @return array
     */
    public function generateKeyTreeFromPath(string $path) : array
    {
        $keys = $this->generateKeysFromPath($path);
        if(empty($keys)) return [];

        array_shift($keys);

        if(count($keys) < 1) return [];

        $tree = [];
        $prev = null;
        foreach($keys as $key) {
            if(is_null($prev)) {
                $prev = &$tree;
            }
            $prev[$key] = [];
            $prev = &$prev[$key];
        }

        return $tree;
    }

    /**
     * @param $tree
     * @param array $keys
     *
     * @return array
     */
    public function addKeysToTree($tree, array $keys) : array
    {
        // if no keys were generated or we're on the root data key, return empty array
        if(empty($keys) || count($keys) == 1) {
            return [];
        }

        // remove first key from list since it is the root data key
        array_shift($keys);

        $current = &$tree;

        for($k = 0; $k < count($keys); $k++) {
            $key = $keys[$k];

            // if we're not the last item keep going until we get there
            if(isset($current[$key]) && $k != count($keys) - 1) {
                $current = &$current[$key];
                continue;
            }

            // create indexes if they exist
            if(!isset($current[$key])) {
                $current[$key] = [];
                $current = &$current[$key];
            }
        }

        return $tree;
    }

    /**
     * @param $tree
     * @param array $keys
     *
     * @return array
     */
    public function removeKeysFromTree(&$tree, array $keys) : array
    {
        $remove = [];

        if(empty($keys)) {
            return ['tree' => $tree, 'remove' => $remove];
        }

        array_shift($keys);

        if(empty($keys)) {
            foreach($tree as $token => $data) {
                $remove[] = $token;
                $remove = $this->extractArrayKeysFromArray($data, $remove);
            }
            return $remove;
        }

        $current = &$tree;
        for($k = 0; $k < count($keys); $k++) {
            $key = $keys[$k];

            // if we're not the last item keep going until we get there
            if(isset($current[$key]) && $k != count($keys) - 1) {
                $current = &$current[$key];
                continue;
            }

            if(isset($current[$key]) && $k == count($keys) - 1) {
                $remove[] = $key;
                $remove = $this->extractArrayKeysFromArray($current[$key], $remove);
                unset($current[$key]);
            }

        }

        return $remove;
    }

    /**
     * @param $data
     * @param array $keys
     *
     * @return array
     */
    private function extractArrayKeysFromArray($data, array $keys = []) : array
    {
        foreach($data as $key => $value) {
            $keys[] = $key;
            if(!empty($value)) {
                $keys = $this->extractArrayKeysFromArray($value, $keys);
            }
        }

        return $keys;
    }
}