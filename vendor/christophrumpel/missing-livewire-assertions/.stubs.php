<?php

namespace Livewire\Features\SupportTesting {

    class Testable {
        public function assertPropertyWired(string $property): self {}

        public function assertPropertyNotWired(string $property): self {}

        public function assertPropertyEntangled(string $property): self {}

        public function assertPropertyNotEntangled(string $property): self {}

        public function assertMethodWired(string $method): self {}

        public function assertMethodNotWired(string $method): self {}

        public function assertMethodWiredToAction(string $methodName, string $action): self {}

        public function assertMethodNotWiredToAction(string $methodName, string $action): self {}

        public function assertMethodWiredToForm(string $method): self {}

        public function assertMethodNotWiredToForm(string $method): self {}

        public function assertMethodWiredToEvent(string $method, string $event): self {}

        public function assertMethodNotWiredToEvent(string $method, string $event): self {}

        public function assertMethodWiredToEventWithoutModifiers(string $method, string $event): self {}

        public function assertMethodNotWiredToEventWithoutModifiers(string $method, string $event): self {}

        public function assertContainsLivewireComponent(string $componentNeedleClass): self {}

        public function assertDoesNotContainLivewireComponent(string $componentNeedleClass): self {}

        public function assertContainsBladeComponent(string $componentNeedleClass): self {}

        public function assertDoesNotContainBladeComponent(string $componentNeedleClass): self {}

        public function assertSeeBefore($valueBefore, $valueAfter): self {}

        public function assertDoNotSeeBefore($valueBefore, $valueAfter): self {}

        public function assertFileDownloadedContains($content): self {}
        
        public function assertFileDownloadedNotContains($content): self {}
    }
}
