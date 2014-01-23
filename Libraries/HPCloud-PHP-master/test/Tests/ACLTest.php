<?php
/* ============================================================================
(c) Copyright 2012 Hewlett-Packard Development Company, L.P.
Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights to
use, copy, modify, merge,publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR 
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.  IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE  LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
============================================================================ */
/**
 * @file
 *
 * Unit tests for ObjectStorage ACLs.
 */
namespace HPCloud\Tests\Storage\ObjectStorage;

require_once 'src/HPCloud/Bootstrap.php';
require_once 'test/TestCase.php';

use \HPCloud\Storage\ObjectStorage\ACL;
/**
 * @ingroup Tests
 */
class ACLTest extends \HPCloud\Tests\TestCase {

  public function testConstructor() {
    $acl = new ACL();
    $this->assertEmpty($acl->rules());

  }

  public function testAddAccount() {
    $acl = new ACL();

    $acl->addAccount(ACL::READ, 'test');

    $rules = $acl->rules();

    $this->assertEquals(1, count($rules));

    $rule = array_shift($rules);

    $this->assertEquals(ACL::READ, $rule['mask']);
    $this->assertEquals('test', $rule['account']);

    // Test with user
    $acl = new ACL();
    $acl->addAccount(ACL::WRITE, 'admin', 'earnie');
    $rules = $acl->rules();
    $rule = array_shift($rules);

    $this->assertEquals(ACL::WRITE, $rule['mask']);
    $this->assertEquals('admin', $rule['account']);
    $this->assertEquals('earnie', $rule['user']);

    // Test with multiple users:
    $acl = new ACL();
    $acl->addAccount(ACL::WRITE, 'admin', array('earnie', 'bert'));
    $rules = $acl->rules();
    $rule = array_shift($rules);

    $this->assertEquals(ACL::WRITE, $rule['mask']);
    $this->assertEquals('admin', $rule['account']);
    $this->assertEquals('earnie', $rule['user'][0]);
    $this->assertEquals('bert', $rule['user'][1]);

  }

  public function testAddReferrer() {
    $acl = new ACL();
    $acl->addReferrer(ACL::READ, '.example.com');
    $acl->addReferrer(ACL::READ_WRITE, '-bad.example.com');

    $rules = $acl->rules();

    $this->assertEquals(2, count($rules));

    $first = array_shift($rules);
    $this->assertEquals(ACL::READ, $first['mask']);
    $this->assertEquals('.example.com', $first['host']);
  }

  public function testAllowListings() {
    $acl = new ACL();
    $acl->allowListings();
    $rules = $acl->rules();

    $this->assertEquals(1, count($rules));
    $this->assertTrue($rules[0]['rlistings']);
    $this->assertEquals(ACL::READ, $rules[0]['mask']);
  }

  public function testHeaders() {
    $acl = new ACL();
    $acl->addAccount(ACL::READ_WRITE, 'test');

    $headers = $acl->headers();

    $this->assertEquals(2, count($headers));
    $read = $headers[ACL::HEADER_READ];
    $write = $headers[ACL::HEADER_WRITE];

    $this->assertEquals('test', $read);
    $this->assertEquals('test', $write);

    // Test hostname rules, which should only appear in READ.
    $acl = new ACL();
    $acl->addReferrer(ACL::READ_WRITE, '.example.com');
    $headers = $acl->headers();

    $this->assertEquals(1, count($headers), print_r($headers, TRUE));
    $read = $headers[ACL::HEADER_READ];

    $this->assertEquals('.r:.example.com', $read);
  }

  public function testToString() {
    $acl = new ACL();
    $acl->addReferrer(ACL::READ_WRITE, '.example.com');

    $str = (string) $acl;

    $this->assertEquals('X-Container-Read: .r:.example.com', $str);
  }

  public function testMakePublic() {
    $acl = (string) ACL::makePublic();

    $this->assertEquals('X-Container-Read: .r:*,.rlistings', $acl);
  }

  public function testMakeNonPublic() {
    $acl = (string) ACL::makeNonPublic();

    $this->assertEmpty($acl);
  }

  public function testNewFromHeaders() {
    $headers = array(
      ACL::HEADER_READ => '.r:.example.com,.rlistings,.r:-*.evil.net',
      ACL::HEADER_WRITE => 'testact2, testact3:earnie, .rlistings  ',
    );

    $acl = ACL::newFromHeaders($headers);

    $rules = $acl->rules();

    $this->assertEquals(6, count($rules));

    // Yay, now we get to test each one.

    $this->assertEquals(ACL::READ, $rules[0]['mask']);
    $this->assertEquals('.example.com', $rules[0]['host']);
    $this->assertTrue($rules[1]['rlistings']);
    $this->assertEquals('-*.evil.net', $rules[2]['host']);

    $this->assertEquals(ACL::WRITE, $rules[3]['mask']);
    $this->assertEquals('testact2', $rules[3]['account']);
    $this->assertEquals('testact3', $rules[4]['account']);
    $this->assertEquals('earnie', $rules[4]['user']);
    $this->assertTrue($rules[5]['rlistings']);

    // Final canary:
    $headers = $acl->headers();
    $read = $headers[ACL::HEADER_READ];
    $write = $headers[ACL::HEADER_WRITE];

    $this->assertEquals('.r:.example.com,.rlistings,.r:-*.evil.net', $read);
    // Note that the spurious .rlistings was removed.
    $this->assertEquals('testact2,testact3:earnie', $write);

  }

  public function testIsNonPublic() {
    $acl = new ACL();

    $this->assertTrue($acl->isNonPublic());

    $acl->addReferrer(ACL::READ, '*.evil.net');
    $this->assertFalse($acl->isNonPublic());

    $acl = ACL::makeNonPublic();
    $this->assertTrue($acl->isNonPublic());
  }

  public function testIsPublic() {
    $acl = new ACL();

    $this->assertFalse($acl->isPublic());
    $acl->allowListings();
    $acl->addReferrer(ACL::READ, '*');

    $this->assertTrue($acl->isPublic());

    $acl->addAccount(ACL::WRITE, 'foo', 'bar');
    $this->assertTrue($acl->isPublic());

    $acl = ACL::makePublic();
    $this->assertTrue($acl->isPublic());
  }

}
