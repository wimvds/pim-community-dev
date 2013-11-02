<?php

namespace Pim\Bundle\ImportExportBundle\Tests\Unit\Cache;

use Pim\Bundle\ImportExportBundle\Cache\AttributeCache;

/**
 * Tests related class
 *
 * @author    Antoine Guigan <antoine@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class AttributeCacheTest extends \PHPUnit_Framework_TestCase
{
    protected $attributeCache;
    protected $doctrine;
    protected $repository;

    protected $attributes;
    protected $expectedQueryCodes;
    protected $families;
    protected $groups;

    protected function setUp()
    {
        $this->attributes = array();
        $this->expectedQueryCodes = null;
        $this->families = array();
        $this->groups = array();
        $this->repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine = $this->getMock('Symfony\Bridge\Doctrine\RegistryInterface');
        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->with($this->equalTo('PimCatalogBundle:ProductAttribute'))
            ->will($this->returnValue($this->repository));
        $this->attributeCache = new AttributeCache($this->doctrine);
    }
    protected function initializeAttributes()
    {
        $this->repository->expects($this->once())
            ->method('findBy')
            ->will($this->returnCallback(array($this, 'getAttributes')));
        $this->addAttribute('identifier', false, false, AttributeCache::IDENTIFIER_ATTRIBUTE_TYPE);
    }
    public function getAttributes($params)
    {
        $this->assertEquals($this->expectedQueryCodes ?: array_keys($this->attributes), array_values($params['code']));

        return $this->attributes;
    }
    public function addAttribute($code, $translatable = false, $scopable = false, $attributeType = 'default')
    {
        $attribute = $this->getMock('Pim\Bundle\CatalogBundle\Entity\ProductAttribute');

        $attribute->expects($this->any())
            ->method('getCode')
            ->will($this->returnValue($code));

        $attribute->expects($this->any())
            ->method('getAttributeType')
            ->will($this->returnValue($attributeType));

        $attribute->expects($this->any())
            ->method('getTranslatable')
            ->will($this->returnValue($translatable));

        $attribute->expects($this->any())
            ->method('getScopable')
            ->will($this->returnValue($scopable));

        $this->attributes[$code] = $attribute;

        return $this->attributes[$code];
    }

    public function testInitialize()
    {
        $this->assertFalse($this->attributeCache->isInitialized());
        $this->initializeAttributes();
        $this->addAttribute('col1');
        $this->addAttribute('col2', true);
        $this->addAttribute('col3', true, true);
        $this->addAttribute('col4', false, true);

        $this->attributeCache->initialize(
            array(
                'identifier',
                'col1',
                'col2-locale1',
                'col2-locale2',
                'col3-locale-scope',
                'col4-scope'
            )
        );

        $this->assertEquals($this->attributes, $this->attributeCache->getAttributes());
        $this->assertEquals($this->attributes['identifier'], $this->attributeCache->getIdentifierAttribute());
        $this->assertEquals($this->attributes['col1'], $this->attributeCache->getAttribute('col1'));
        $this->assertEquals(
            array(
                'identifier' => array(
                    'code'      => 'identifier',
                    'locale'    => null,
                    'scope'     => null,
                    'attribute' => $this->attributes['identifier']
                ),
                'col1' => array(
                    'code'      => 'col1',
                    'locale'    => null,
                    'scope'     => null,
                    'attribute' => $this->attributes['col1']
                ),
                'col2-locale1' => array(
                    'code'      => 'col2',
                    'locale'    => 'locale1',
                    'scope'     => null,
                    'attribute' => $this->attributes['col2']
                ),
                'col2-locale2' => array(
                    'code'      => 'col2',
                    'locale'    => 'locale2',
                    'scope'     => null,
                    'attribute' => $this->attributes['col2']
                ),
                'col3-locale-scope' => array(
                    'code'      => 'col3',
                    'locale'    => 'locale',
                    'scope'     => 'scope',
                    'attribute' => $this->attributes['col3']
                ),
                'col4-scope' => array(
                    'code'      => 'col4',
                    'locale'    => null,
                    'scope'     => 'scope',
                    'attribute' => $this->attributes['col4']
                )
            ),
            $this->attributeCache->getColumns()
        );
        $this->assertTrue($this->attributeCache->isInitialized());
    }

    public function testClear()
    {
        $this->initializeAttributes();
        $this->attributeCache->initialize(array('identifier'));
        $this->attributeCache->clear();
        $this->assertFalse($this->attributeCache->isInitialized());
        $this->assertNull($this->attributeCache->getAttributes());
        $this->assertNull($this->attributeCache->getColumns());
        $this->assertNull($this->attributeCache->getIdentifierAttribute());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The column "col" must contain the local code
     */
    public function testColumnWithoutLocale()
    {
        $this->initializeAttributes();
        $this->expectedQueryCodes = array('identifier', 'col');
        $this->addAttribute('col', true);

        $this->attributeCache->initialize(array('identifier', 'col'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The column "col" must contain the scope code
     */
    public function testColumnWithoutScope()
    {
        $this->initializeAttributes();
        $this->expectedQueryCodes = array('identifier', 'col');
        $this->addAttribute('col', false, true);

        $this->attributeCache->initialize(array('identifier', 'col'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The column "col" must contain the local code
     */
    public function testColumnWithoutLocalAndScope()
    {
        $this->initializeAttributes();
        $this->expectedQueryCodes = array('identifier', 'col');
        $this->addAttribute('col', true, true);

        $this->attributeCache->initialize(array('identifier', 'col'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The following fields do not exist: col1, col2
     */
    public function testExtraColumns()
    {
        $this->initializeAttributes();
        $this->expectedQueryCodes = array('identifier', 'col1', 'col2');

        $this->attributeCache->initialize(array('identifier', 'col1', 'col2'));
    }

    public function testGetRequiredAttributes()
    {
        $product1 = $this->getProductMock(
            null,
            array(),
            'family1',
            array('key1', 'key2'),
            array('group1' => array('key3', 'key4', 'key5'), 'group2' => array('key7'))
        );
        $this->assertEqualArrays(
            array('key1', 'key2', 'key3', 'key4', 'key5', 'key7'),
            $this->attributeCache->getRequiredAttributeCodes($product1)
        );

        $product2 = $this->getProductMock(
            1,
            array('key0'),
            'family2',
            array('key8'),
            array('group1' => array(), 'group3' => array('key3', 'key9'))
        );
        $this->assertEqualArrays(
            array('key0', 'key3', 'key4', 'key5', 'key8', 'key9'),
            $this->attributeCache->getRequiredAttributeCodes($product2)
        );

        $product3 = $this->getProductMock(
            null,
            array(),
            'family1'
        );
        $this->assertEqualArrays(
            array('key1', 'key2'),
            $this->attributeCache->getRequiredAttributeCodes($product3)
        );

        $product4 = $this->getProductMock();
        $this->assertEqualArrays(
            array(),
            $this->attributeCache->getRequiredAttributeCodes($product4)
        );
    }

    protected function assertEqualArrays($expected, $actual)
    {
        sort($expected);
        sort($actual);

        return $this->assertEquals($expected, $actual);
    }

    protected function getProductMock(
        $productId = null,
        $productAttributeCodes = array(),
        $familyCode = null,
        array $familyAttributeCodes = array(),
        array $categories = array()
    ) {
        $product = $this->getMockBuilder('Pim\Bundle\CatalogBundle\Model\ProductInterface')
            ->setMethods(array('getId', 'getValues', 'getFamily', 'getGroups'))
            ->getMock();
        $product->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($productId));
        $values = array();
        foreach ($productAttributeCodes as $productAttributeCode) {
            $value = $this->getMockBuilder('Pim\Bundle\CatalogBundle\Model\ProductValueInterface')
                ->setMethods(array('getAttribute', '__toString'))
                ->getMock();
            $value->expects($this->any())
                ->method('getAttribute')
                ->will($this->returnValue($this->addAttribute($productAttributeCode)));
            $values[] = $value;
        }
        $product->expects($this->any())
            ->method('getValues')
            ->will($this->returnValue($values));

        if (null !== $familyCode) {
            if (!isset($this->families[$familyCode])) {
                $this->families[$familyCode] = $this->getMock('Pim\Bundle\CatalogBundle\Entity\Family');
                $this->addAttributeCollection($this->families[$familyCode], $familyCode, $familyAttributeCodes);
            }
            $product
                ->expects($this->any())
                ->method('getFamily')
                ->will($this->returnValue($this->families[$familyCode]));
        }

        $groups = array();
        foreach ($categories as $groupCode => $groupAttributeCodes) {
            if (!isset($this->groups[$groupCode])) {
                $this->groups[$groupCode] = $this->getMock('Pim\Bundle\CatalogBundle\Entity\Group');
                $this->addAttributeCollection($this->groups[$groupCode], $groupCode, $groupAttributeCodes);
            }
            $groups[] = $this->groups[$groupCode];
        }
        $product
            ->expects($this->any())
            ->method('getGroups')
            ->will($this->returnValue($groups));

        return $product;
    }

    protected function addAttributeCollection($entity, $code, array $attributeCodes)
    {
        $test = $this;
        $collection = $this->getMock('Doctrine\Common\Collections\ArrayCollection');
        $collection->expects($this->once())
            ->method('toArray')
            ->will(
                $this->returnValue(
                    array_map(
                        function ($code) use ($test) {
                            return $test->addAttribute($code);
                        },
                        $attributeCodes
                    )
                )
            );
        $entity->expects($this->any())
                ->method('getCode')
                ->will($this->returnValue($code));
        $entity->expects($this->once())
            ->method('getAttributes')
            ->will($this->returnValue($collection));

        return $collection;
    }
}
