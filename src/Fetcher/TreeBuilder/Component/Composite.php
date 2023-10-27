<?php

    namespace Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component;

    use Doctrine\Persistence\Mapping\ClassMetadata;
    use Verclam\SmartFetchBundle\Fetcher\Visitor\SmartFetchVisitorInterface;

    class Composite extends Component
    {
        /**
         * @var Component[]
         */
        private array $children = [];

        public function __construct($root = false)
        {
            $this->isRoot = $root;
            parent::__construct();
        }

        public function isComposite(): bool
        {
            return true;
        }

        public function getChildren(): array
        {
            return $this->children;
        }

        public function addChild(ComponentInterface $child): static
        {
            $this->children[] = $child;
            $child->setParent($this);
            return $this;
        }

        public function handle(SmartFetchVisitorInterface $visitor): void
        {
            if(!$this->isInitialized()){
                $visitor->generate($this);
            }else{
                $visitor->addPath($this);
            }

            foreach ($this->children as $child) {
                $child->handle($visitor);
            }

        }
    }