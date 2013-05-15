<?php

namespace Pim\Bundle\ProductBundle\Entity\Repository;

use Pim\Bundle\ProductBundle\Doctrine\EntityRepository;

/**
 * @author    Gildas Quemener <gildas.quemener@gmail.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductFamilyRepository extends EntityRepository
{
    public function buildOneWithAttributes($id)
    {
        return $this
            ->buildOne($id)
            ->addSelect('attribute')
            ->leftJoin('product_family.attributes', 'attribute')
            ->leftJoin('attribute.group', 'group')
            ->orderBy('attribute.group', 'DESC');
        ;
    }
}
