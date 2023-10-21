<?php

    namespace Verclam\SmartFetchBundle\Attributes;

    interface SmartFetchInterface
    {
        /**
         * Returns the parameter class name.
         *
         * @return string
         */
        public function getClass(): ?string;

        /**
         * @return mixed
         */
        public function getArgumentName(): mixed;

        public function getQueryName(): string;
    }