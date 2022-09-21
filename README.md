# Cathy

example.php
```php
class Cache
{
    private $cache;

    private $cathy;

    public function __construct(
        CacheDriverInterface $cache,
        Cathy $cathy = null
    ){
        $this->cache = $cache;
        $this->cathy = $cathy ?? new Cathy();
    }

    public function connect()
    {
        return $this->cache->connect();
    }

    public function get($key)
    {
        $keys = $this->cathy->generateKeysFromPath($key);
        return $this->cache->get(end($keys));
    }

    public function getRaw($key)
    {
        return $this->cache->get($key);
    }

    public function set($key, $data, $ttl = 0)
    {
        $keys = $this->cathy->generateKeysFromPath($key);
        $tree = $this->cathy->generateKeyTreeFromPath($key);

        // store data early if necessary
        $path = current($keys) . "-data";

        $cache_data = $tree;
        if($this->cache->exists($path)) {
            $cache_data = $this->getRaw($path);
            if(!empty($cache_data)) {
                try {
                    $cache_data = unserialize($cache_data);
                    $cache_data = $this->cathy->addKeysToTree($cache_data, $keys);
                } catch(\Exception $e) {
                    $cache_data = $tree;
                }
            }
        }

        // store cache tree data no timer
        $this->setRaw($path, serialize($cache_data));

        // store cache data
        return $this->setRaw(end($keys), $data, $ttl);
    }

    public function setRaw($key, $data, $ttl = 0)
    {
        return $this->cache->set($key, $data, $ttl);
    }

    public function remove($key)
    {
        $keys = $this->cathy->generateKeysFromPath($key);

        // store data early if necessary
        $path = current($keys) . "-data";

        $remove = $keys;

        // update root path key tree
        if($this->cache->exists($path)) {
            $cache_data = $this->getRaw($path);
            if(!empty($cache_data)) {
                try {
                    $cache_data = unserialize($cache_data);
                    $remove = $this->cathy->removeKeysFromTree($cache_data, $keys);
                    // store cache tree data no timer
                    $this->setRaw($path, serialize($cache_data));
                } catch(\Exception $e) {
                }
            }
        }

        // delete child keys from list
        foreach($remove as $k) {
            $this->removeRaw($k);
        }

        // delete last key in keys list
        $this->removeRaw(end($keys));
    }

    public function removeRaw($key)
    {
        return $this->cache->remove($key);
    }
}
```