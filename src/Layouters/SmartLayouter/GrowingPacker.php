<?php
namespace Cyantree\Mosaic\Layouters\SmartLayouter;

/**
 * Class GrowingPacker
 * Powered by https://github.com/jakesgordon/bin-packing/
 */
class GrowingPacker
{
    public $maxWidth;
    public $maxHeight;

    private function findNode(GrowingPackerNode $node, $width, $height)
    {
        if ($node->used) {
            if ($result = $this->findNode($node->right, $width, $height)) {
                return $result;
            }
            elseif ($result = $this->findNode($node->down, $width, $height)) {
                return $result;
            }
            else {
                return null;
            }
        }
        elseif (($width <= $node->width) && ($height <= $node->height)) {
            return $node;
        }
        else {
            return null;
        }
    }

    private function splitNode(GrowingPackerNode $node, $width, $height)
    {
        $node->used = true;
        $node->down = new GrowingPackerNode($node->x, $node->y + $height, $node->width, $node->height - $height);
        $node->right = new GrowingPackerNode($node->x + $width, $node->y, $node->width - $width, $height);

        return $node;
    }

    private function growNode(GrowingPackerResult $result, $width, $height)
    {
        $canGrowDown = $width <= $result->root->width && (!$this->maxHeight || ($result->root->height + $height) < $this->maxHeight); // RICHTIG SO?
        $canGrowRight = $height <= $result->root->height && (!$this->maxWidth || ($result->root->width + $width) < $this->maxWidth);

        $shouldGrowRight = $canGrowRight && $result->root->height >= ($result->root->width + $width);
        $shouldGrowDown = $canGrowDown && $result->root->width >= ($result->root->height + $height);

        if ($shouldGrowRight) {
            return $this->growRight($result, $width, $height);
        }
        elseif ($shouldGrowDown) {
            return $this->growDown($result, $width, $height);
        }
        elseif ($canGrowRight) {
            return $this->growRight($result, $width, $height);
        }
        elseif ($canGrowDown) {
            return $this->growDown($result, $width, $height);
        }
        else {
            return null;
        }
    }

    private function growRight(GrowingPackerResult $result, $width, $height)
    {
        $newRoot = new GrowingPackerNode(0, 0, $result->root->width + $width, $result->root->height);
        $newRoot->used = true;
        $newRoot->down = $result->root;
        $newRoot->right = new GrowingPackerNode($result->root->width, 0, $width, $result->root->height);

        $result->root = $newRoot;

        if ($node = $this->findNode($result->root, $width, $height)) {
            return $this->splitNode($node, $width, $height);
        }
        else {
            return null;
        }
    }

    private function growDown(GrowingPackerResult $result, $width, $height)
    {
        $newRoot = new GrowingPackerNode(0, 0, $result->root->width, $result->root->height + $height);
        $newRoot->used = true;
        $newRoot->down = new GrowingPackerNode(0, $result->root->height, $result->root->width, $height);
        $newRoot->right = $result->root;

        $result->root = $newRoot;

        if ($node = $this->findNode($result->root, $width, $height)) {
            return $this->splitNode($node, $width, $height);
        }
        else {
            return null;
        }
    }

    /**
     * @param $blocks GrowingPackerBlock[]
     * @return GrowingPackerBlock[]
     */
    public function sortBlocks($blocks)
    {
        $sets = [];

        foreach ($blocks as $block) {
            $maxSize = max($block->width, $block->height);

            if (!isset($sets[$maxSize])) {
                $sets[$maxSize] = [$block];
            }
            else {
                $sets[$maxSize][] = $block;
            }
        }

        krsort($sets);

        /** @var GrowingPackerBlock[] $blocks */
        $blocks = [];
        foreach ($sets as $set) {
            foreach ($set as $block) {
                $blocks[] = $block;
            }
        }

        return $blocks;
    }

    public function pack($blocks, GrowingPackerResult $result = null)
    {
        if (!is_array($blocks)) {
            $blocks = [$blocks];
        }

        /** @var $blocks GrowingPackerBlock[] */

        if (!$result) {
            $result = new GrowingPackerResult();
        }

        if (!$result->root) {
            $result->initialize($blocks[0]);
        }

        foreach ($blocks as $block) {
            if ($node = $this->findNode($result->root, $block->width, $block->height)) {
                $block->node = $this->splitNode($node, $block->width, $block->height);
            }
            else {
                $block->node = $this->growNode($result, $block->width, $block->height);
            }

            if ($block->node) {
                $result->addPackedBlock($block);
            }
            else {
                $result->addUnpackedBlock($block);
            }
        }

        return $result;
    }
}
