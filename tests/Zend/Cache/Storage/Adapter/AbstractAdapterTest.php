<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Cache
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace ZendTest\Cache\Storage\Adapter;

use Zend\Cache,
    Zend\Cache\Exception;

/**
 * @category   Zend
 * @package    Zend_Cache
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_Cache
 */
class AbstractAdapterTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Mock of the abstract storage adapter
     *
     * @var Zend\Cache\Storage\Adapter\AbstractAdapter
     */
    protected $_storage;

    public function setUp()
    {
        $this->_options = new Cache\Storage\Adapter\AdapterOptions();
    }

    public function testGetOptions()
    {
        $this->_storage = $this->getMockForAbstractAdapter();

        $options = $this->_storage->getOptions();
        $this->assertInstanceOf('Zend\Cache\Storage\Adapter\AdapterOptions', $options);
        $this->assertInternalType('boolean', $options->getWritable());
        $this->assertInternalType('boolean', $options->getReadable());
        $this->assertInternalType('integer', $options->getTtl());
        $this->assertInternalType('string', $options->getNamespace());
        $this->assertInternalType('string', $options->getNamespacePattern());
        $this->assertInternalType('string', $options->getKeyPattern());
        $this->assertInternalType('boolean', $options->getIgnoreMissingItems());
    }

    public function testSetWritable()
    {
        $this->_options->setWritable(true);
        $this->assertTrue($this->_options->getWritable());

        $this->_options->setWritable(false);
        $this->assertFalse($this->_options->getWritable());
    }

    public function testSetReadable()
    {
        $this->_options->setReadable(true);
        $this->assertTrue($this->_options->getReadable());

        $this->_options->setReadable(false);
        $this->assertFalse($this->_options->getReadable());
    }

    public function testSetTtl()
    {
        $this->_options->setTtl('123');
        $this->assertSame(123, $this->_options->getTtl());
    }

    public function testSetTtlThrowsInvalidArgumentException()
    {
        $this->setExpectedException('Zend\Cache\Exception\InvalidArgumentException');
        $this->_options->setTtl(-1);
    }

    public function testGetDefaultNamespaceNotEmpty()
    {
        $ns = $this->_options->getNamespace();
        $this->assertNotEmpty($ns);
    }

    public function testSetNamespace()
    {
        $this->_options->setNamespace('new_namespace');
        $this->assertSame('new_namespace', $this->_options->getNamespace());
    }

    public function testSetNamespacePattern()
    {
        $pattern = '/^.*$/';
        $this->_options->setNamespacePattern($pattern);
        $this->assertEquals($pattern, $this->_options->getNamespacePattern());
    }

    public function testUnsetNamespacePattern()
    {
        $this->_options->setNamespacePattern(null);
        $this->assertSame('', $this->_options->getNamespacePattern());
    }

    public function testSetNamespace0()
    {
        $this->_options->setNamespace('0');
        $this->assertSame('0', $this->_options->getNamespace());
    }

    public function testSetNamespacePatternThrowsExceptionOnInvalidPattern()
    {
        $this->setExpectedException('Zend\Cache\Exception\InvalidArgumentException');
        $this->_options->setNamespacePattern('#');
    }

    public function testSetNamespacePatternThrowsExceptionOnInvalidNamespace()
    {
        $this->_options->setNamespace('ns');
        $this->setExpectedException('Zend\Cache\Exception\RuntimeException');
        $this->_options->setNamespacePattern('/[abc]/');
    }

    public function testSetKeyPattern()
    {
        $this->_options->setKeyPattern('/^[key]+$/Di');
        $this->assertEquals('/^[key]+$/Di', $this->_options->getKeyPattern());
    }

    public function testUnsetKeyPattern()
    {
        $this->_options->setKeyPattern(null);
        $this->assertSame('', $this->_options->getKeyPattern());
    }

    public function testSetKeyPatternThrowsExceptionOnInvalidPattern()
    {
        $this->setExpectedException('Zend\Cache\Exception\InvalidArgumentException');
        $this->_options->setKeyPattern('#');
    }

    public function testSetIgnoreMissingItems()
    {
        $this->_options->setIgnoreMissingItems(true);
        $this->assertTrue($this->_options->getIgnoreMissingItems());

        $this->_options->setIgnoreMissingItems(false);
        $this->assertFalse($this->_options->getIgnoreMissingItems());
    }

    public function testPluginRegistry()
    {
        $this->_storage = $this->getMockForAbstractAdapter();

        $plugin = new \ZendTest\Cache\Storage\TestAsset\MockPlugin();

        // no plugin registered
        $this->assertFalse($this->_storage->hasPlugin($plugin));
        $this->assertEquals(0, count($this->_storage->getPlugins()));
        $this->assertEquals(0, count($plugin->getHandles()));

        // register a plugin
        $this->assertSame($this->_storage, $this->_storage->addPlugin($plugin));
        $this->assertTrue($this->_storage->hasPlugin($plugin));
        $this->assertEquals(1, count($this->_storage->getPlugins()));

        // test registered callback handles
        $handles = $plugin->getHandles();
        $this->assertEquals(1, count($handles));
        $this->assertEquals(count($plugin->getEventCallbacks()), count(current($handles)));

        // test unregister a plugin
        $this->assertSame($this->_storage, $this->_storage->removePlugin($plugin));
        $this->assertFalse($this->_storage->hasPlugin($plugin));
        $this->assertEquals(0, count($this->_storage->getPlugins()));
        $this->assertEquals(0, count($plugin->getHandles()));
    }

    public function testInternalTriggerPre()
    {
        $this->_storage = $this->getMockForAbstractAdapter();

        $plugin = new \ZendTest\Cache\Storage\TestAsset\MockPlugin();
        $this->_storage->addPlugin($plugin);

        $params = new \ArrayObject(array(
            'key'   => 'key1',
            'value' => 'value1'
        ));

        // call protected method
        $method = new \ReflectionMethod(get_class($this->_storage), 'triggerPre');
        $method->setAccessible(true);
        $rsCollection = $method->invoke($this->_storage, 'setItem', $params);
        $this->assertInstanceOf('Zend\EventManager\ResponseCollection', $rsCollection);

        // test called event
        $calledEvents = $plugin->getCalledEvents();
        $this->assertEquals(1, count($calledEvents));

        $event = current($calledEvents);
        $this->assertInstanceOf('Zend\Cache\Storage\Event', $event);
        $this->assertEquals('setItem.pre', $event->getName());
        $this->assertSame($this->_storage, $event->getTarget());
        $this->assertSame($params, $event->getParams());
    }

    public function testInternalTriggerPost()
    {
        $this->_storage = $this->getMockForAbstractAdapter();

        $plugin = new \ZendTest\Cache\Storage\TestAsset\MockPlugin();
        $this->_storage->addPlugin($plugin);

        $params = new \ArrayObject(array(
            'key'   => 'key1',
            'value' => 'value1'
        ));
        $result = true;

        // call protected method
        $method = new \ReflectionMethod(get_class($this->_storage), 'triggerPost');
        $method->setAccessible(true);
        $result = $method->invokeArgs($this->_storage, array('setItem', $params, &$result));

        // test called event
        $calledEvents = $plugin->getCalledEvents();
        $this->assertEquals(1, count($calledEvents));
        $event = current($calledEvents);

        // return value of triggerPost and the called event should be the same
        $this->assertSame($result, $event->getResult());

        $this->assertInstanceOf('Zend\Cache\Storage\PostEvent', $event);
        $this->assertEquals('setItem.post', $event->getName());
        $this->assertSame($this->_storage, $event->getTarget());
        $this->assertSame($params, $event->getParams());
        $this->assertSame($result, $event->getResult());
    }

    public function testInternalTriggerExceptionThrowRuntimeException()
    {
        $this->_storage = $this->getMockForAbstractAdapter();

        $plugin = new \ZendTest\Cache\Storage\TestAsset\MockPlugin();
        $this->_storage->addPlugin($plugin);

        $params = new \ArrayObject(array(
            'key'   => 'key1',
            'value' => 'value1'
        ));

        // call protected method
        $method = new \ReflectionMethod(get_class($this->_storage), 'triggerException');
        $method->setAccessible(true);

        $this->setExpectedException('Zend\Cache\Exception\RuntimeException', 'test');
        $method->invokeArgs($this->_storage, array('setItem', $params, new Exception\RuntimeException('test')));
    }

    public function testGetItemCallsInternalGetItem()
    {
        $this->_storage = $this->getMockForAbstractAdapter(array('internalGetItem'));

        $options = array('ttl' => 123);
        $key     = 'key1';
        $result  = 'value1';

        $this->_storage
            ->expects($this->once())
            ->method('internalGetItem')
            ->with($this->equalTo($key), $this->equalTo($this->normalizeOptions($options)))
            ->will($this->returnValue($result));

        $rs = $this->_storage->getItem($key, $options);
        $this->assertEquals($result, $rs);
    }

    public function testGetItemsCallsInternalGetItems()
    {
        $this->_storage = $this->getMockForAbstractAdapter(array('internalGetItems'));

        $options = array('ttl' => 123);
        $keys    = array('key1', 'key2');
        $result  = array('key2' => 'value2');

        $this->_storage
            ->expects($this->once())
            ->method('internalGetItems')
            ->with($this->equalTo($keys), $this->equalTo($this->normalizeOptions($options)))
            ->will($this->returnValue($result));

        $rs = $this->_storage->getItems($keys, $options);
        $this->assertEquals($result, $rs);
    }

    public function testInternalGetItemsCallsInternalGetItemForEachKey()
    {
        $this->_storage = $this->getMockForAbstractAdapter(array('internalGetItem'));

        $options = array('ttl' => 123);
        $items   = array('key1' => 'value1', 'notFound' => false, 'key2' => 'value2');
        $result  = array('key1' => 'value1', 'key2' => 'value2');

        $normalizedOptions = $this->normalizeOptions($options);
        $normalizedOptions['ignore_missing_items'] = false;

        $i = 0; // method call counter
        foreach ($items as $k => $v) {
            $this->_storage->expects($this->at($i++))
                ->method('internalGetItem')
                ->with($this->equalTo($k), $this->equalTo($normalizedOptions))
                // return value or throw ItemNotFoundException
                ->will($v ? $this->returnValue($v) : $this->throwException(new Exception\ItemNotFoundException()));
        }

        $rs = $this->_storage->getItems(array_keys($items), $options);
        $this->assertEquals($result, $rs);
    }

    public function testHasItemCallsInternalHasItem()
    {
        $this->_storage = $this->getMockForAbstractAdapter(array('internalHasItem'));

        $options = array('ttl' => 123);
        $key     = 'key1';
        $result  = true;

        $this->_storage
            ->expects($this->once())
            ->method('internalHasItem')
            ->with($this->equalTo($key), $this->equalTo($this->normalizeOptions($options)))
            ->will($this->returnValue($result));

        $rs = $this->_storage->hasItem($key, $options);
        $this->assertSame($result, $rs);
    }

    public function testInternalHasItemCallsInternalGetItemReturnsTrueOnValidFalseValue()
    {
        $this->_storage = $this->getMockForAbstractAdapter(array('internalGetItem'));

        $options = array('ttl' => 123);
        $key     = 'key1';

        $this->_storage
            ->expects($this->once())
            ->method('internalGetItem')
            ->with($this->equalTo($key), $this->equalTo($this->normalizeOptions($options)))
            ->will($this->returnValue(false)); // return a valid false value

        $rs = $this->_storage->hasItem($key, $options);
        $this->assertTrue($rs);
    }

    public function testInternalHasItemCallsInternalGetItemReturnsFalseOnItemNotFoundException()
    {
        $this->_storage = $this->getMockForAbstractAdapter(array('internalGetItem'));

        $options = array('ttl' => 123);
        $key     = 'key1';

        $this->_storage
            ->expects($this->once())
            ->method('internalGetItem')
            ->with($this->equalTo($key), $this->equalTo($this->normalizeOptions($options)))
            ->will($this->throwException(new Exception\ItemNotFoundException())); // throw ItemNotFoundException

        $rs = $this->_storage->hasItem($key, $options);
        $this->assertFalse($rs);
    }

    public function testHasItemsCallsInternalHasItems()
    {
        $this->_storage = $this->getMockForAbstractAdapter(array('internalHasItems'));

        $options = array('ttl' => 123);
        $keys    = array('key1', 'key2');
        $result  = array('key2');

        $this->_storage
            ->expects($this->once())
            ->method('internalHasItems')
            ->with($this->equalTo($keys), $this->equalTo($this->normalizeOptions($options)))
            ->will($this->returnValue($result));

        $rs = $this->_storage->hasItems($keys, $options);
        $this->assertEquals($result, $rs);
    }

    public function testInternalHasItemsCallsInternalHasItem()
    {
        $this->_storage = $this->getMockForAbstractAdapter(array('internalHasItem'));

        $options = array('ttl' => 123);
        $items   = array('key1' => true, 'key2' => false);
        $result  = array('key1');

        $i = 0; // method call counter
        foreach ($items as $k => $v) {
            $this->_storage
                ->expects($this->at($i++))
                ->method('internalHasItem')
                ->with($this->equalTo($k), $this->equalTo($this->normalizeOptions($options)))
                ->will($this->returnValue($v));
        }

        $rs = $this->_storage->hasItems(array_keys($items), $options);
        $this->assertEquals($result, $rs);
    }

    public function testGetMetadataCallsInternalGetMetadata()
    {
        $this->_storage = $this->getMockForAbstractAdapter(array('internalGetMetadata'));

        $options = array('ttl' => 123);
        $key     = 'key1';
        $result  = array();

        $this->_storage
            ->expects($this->once())
            ->method('internalGetMetadata')
            ->with($this->equalTo($key), $this->equalTo($this->normalizeOptions($options)))
            ->will($this->returnValue($result));

        $rs = $this->_storage->getMetadata($key, $options);
        $this->assertSame($result, $rs);
    }

/*
    public function testGetMetadatas()
    {
        $options    = array('ttl' => 123);
        $items      = array(
            'key1'  => array('meta1' => 1),
            'dKey1' => false,
            'key2'  => array('meta2' => 2),
        );

        $i = 0;
        foreach ($items as $k => $v) {
            $this->_storage->expects($this->at($i++))
                ->method('getMetadata')
                ->with($this->equalTo($k), $this->equalTo($options))
                ->will($this->returnValue($v));
        }

        $rs = $this->_storage->getMetadatas(array_keys($items), $options);

        // remove missing items from array to test
        $expected = $items;
        foreach ($expected as $key => $value) {
            if (false === $value) {
                unset($expected[$key]);
            }
        }

        $this->assertEquals($expected, $rs);
    }

    public function testSetItems()
    {
        $options = array('ttl' => 123);
        $items   = array(
            'key1' => 'value1',
            'key2' => 'value2'
        );

        $this->_storage->expects($this->exactly(count($items)))
            ->method('setItem')
            ->with($this->stringContains('key'), $this->stringContains('value'), $this->equalTo($options))
            ->will($this->returnValue(true));

        $this->assertTrue($this->_storage->setItems($items, $options));
    }

    public function testSetItemsFail()
    {
        $options = array('ttl' => 123);
        $items   = array(
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        );

        $this->_storage->expects($this->exactly(count($items)))
            ->method('setItem')
            ->with($this->stringContains('key'), $this->stringContains('value'), $this->equalTo($options))
            ->will($this->returnValue(false));

        $this->assertFalse($this->_storage->setItems($items, $options));
    }

    public function testAddItems()
    {
        $options = array('ttl' => 123);
        $items   = array(
            'key1' => 'value1',
            'key2' => 'value2'
        );

        // add -> has -> get -> set
        $this->_storage->expects($this->exactly(count($items)))
            ->method('getItem')
            ->with($this->stringContains('key'), $this->equalTo($options))
            ->will($this->returnValue(false));
        $this->_storage->expects($this->exactly(count($items)))
            ->method('setItem')
            ->with($this->stringContains('key'), $this->stringContains('value'), $this->equalTo($options))
            ->will($this->returnValue(true));

        $this->assertTrue($this->_storage->addItems($items, $options));
    }

    public function testAddItemsFail()
    {
        $options = array('ttl' => 123);
        $items   = array(
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        );

        // add -> has -> get -> set
        $this->_storage->expects($this->exactly(count($items)))
            ->method('getItem')
            ->with($this->stringContains('key'), $this->equalTo($options))
            ->will($this->returnValue(false));
        $this->_storage->expects($this->exactly(count($items)))
            ->method('setItem')
            ->with($this->stringContains('key'), $this->stringContains('value'), $this->equalTo($options))
            ->will($this->returnValue(false));

        $this->assertFalse($this->_storage->addItems($items, $options));
    }

    public function testReplaceItems()
    {
        $options = array('ttl' => 123);
        $items   = array(
            'key1' => 'value1',
            'key2' => 'value2'
        );

        // replace -> has -> get -> set
        $this->_storage->expects($this->exactly(count($items)))
            ->method('getItem')
            ->with($this->stringContains('key'), $this->equalTo($options))
            ->will($this->returnValue(true));
        $this->_storage->expects($this->exactly(count($items)))
            ->method('setItem')
            ->with($this->stringContains('key'), $this->stringContains('value'), $this->equalTo($options))
            ->will($this->returnValue(true));

        $this->assertTrue($this->_storage->replaceItems($items, $options));
    }

    public function testReplaceItemsFail()
    {
        $options = array('ttl' => 123);
        $items   = array(
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        );

        // replace -> has -> get -> set
        $this->_storage->expects($this->exactly(count($items)))
            ->method('getItem')
            ->with($this->stringContains('key'), $this->equalTo($options))
            ->will($this->returnValue(true));
        $this->_storage->expects($this->exactly(count($items)))
            ->method('setItem')
            ->with($this->stringContains('key'), $this->stringContains('value'), $this->equalTo($options))
            ->will($this->returnValue(false));

        $this->assertFalse($this->_storage->replaceItems($items, $options));
    }

    public function testRemoveItems()
    {
        $options = array('ttl' => 123);
        $keys    = array('key1', 'key2');

        foreach ($keys as $i => $key) {
            $this->_storage->expects($this->at($i))
                           ->method('removeItem')
                           ->with($this->equalTo($key), $this->equalTo($options))
                           ->will($this->returnValue(true));
        }

        $this->assertTrue($this->_storage->removeItems($keys, $options));
    }

    public function testRemoveItemsFail()
    {
        $options = array('ttl' => 123);
        $items   = array('key1', 'key2', 'key3');

        $this->_storage->expects($this->at(0))
                       ->method('removeItem')
                       ->with($this->equalTo('key1'), $this->equalTo($options))
                       ->will($this->returnValue(true));
        $this->_storage->expects($this->at(1))
                       ->method('removeItem')
                       ->with($this->equalTo('key2'), $this->equalTo($options))
                       ->will($this->returnValue(false)); // -> fail
        $this->_storage->expects($this->at(2))
                       ->method('removeItem')
                       ->with($this->equalTo('key3'), $this->equalTo($options))
                       ->will($this->returnValue(true));

        $this->assertFalse($this->_storage->removeItems($items, $options));
    }
*/
    // TODO: getDelayed + fatch[All]
    // TODO: incrementItem[s] + decrementItem[s]
    // TODO: touchItem[s]

    /**
     * Generates a mock of the abstract storage adapter by mocking all abstract and the given methods
     * Also sets the adapter options
     *
     * @param array $methods
     * @return \Zend\Cache\Storage\Adapter\AbstractAdapter
     */
    protected function getMockForAbstractAdapter(array $methods = array())
    {
        $class = 'Zend\Cache\Storage\Adapter\AbstractAdapter';

        if (!$methods) {
            $adapter = $this->getMockForAbstractClass($class);
        } else {
            $reflection = new \ReflectionClass('Zend\Cache\Storage\Adapter\AbstractAdapter');
            foreach ($reflection->getMethods() as $method) {
                if ($method->isAbstract()) {
                    $methods[] = $method->getName();
                }
            }
            $adapter = $this->getMockBuilder($class)->setMethods(array_unique($methods))->getMock();
        }

        $adapter->setOptions($this->_options);
        return $adapter;
    }

    protected function normalizeOptions($options)
    {
        // ttl
        if (!isset($options['ttl'])) {
            $options['ttl'] = $this->_options->getTtl();
        }

        // namespace
        if (!isset($options['namespace'])) {
            $options['namespace'] = $this->_options->getNamespace();
        }

        // ignore_missing_items
        if (!isset($options['ignore_missing_items'])) {
            $options['ignore_missing_items'] = $this->_options->getIgnoreMissingItems();
        }

        // tags
        if (!isset($options['tags'])) {
            $options['tags'] = null;
        }

        // select
        if (!isset($options['select'])) {
            $options['select'] = array('key', 'value');
        }

        return $options;
    }

}
