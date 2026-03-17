<?php

use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Models\Action;
use Wirechat\Wirechat\Models\Attachment;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Group;
use Wirechat\Wirechat\Models\Message;
use Wirechat\Wirechat\Models\Participant;

describe('WirechatService Model Resolution', function () {
    beforeEach(function () {
        // Reset config to default values before each test
        config([
            'wirechat.models.action' => Action::class,
            'wirechat.models.attachment' => Attachment::class,
            'wirechat.models.conversation' => Conversation::class,
            'wirechat.models.group' => Group::class,
            'wirechat.models.message' => Message::class,
            'wirechat.models.participant' => Participant::class,
        ]);
    });

    describe('Model Class Methods', function () {
        it('returns correct action model class', function () {
            expect(Wirechat::actionModelClass())->toBe(Action::class);
        });

        it('returns correct attachment model class', function () {
            expect(Wirechat::attachmentModelClass())->toBe(Attachment::class);
        });

        it('returns correct conversation model class', function () {
            expect(Wirechat::conversationModelClass())->toBe(Conversation::class);
        });

        it('returns correct group model class', function () {
            expect(Wirechat::groupModelClass())->toBe(Group::class);
        });

        it('returns correct message model class', function () {
            expect(Wirechat::messageModelClass())->toBe(Message::class);
        });

        it('returns correct participant model class', function () {
            expect(Wirechat::participantModelClass())->toBe(Participant::class);
        });
    });

    describe('Model Instance Creation', function () {
        it('creates action model instance', function () {
            $model = Wirechat::actionModel([
                'actionable_id' => 1,
                'actionable_type' => 'test',
                'actor_id' => 1,
                'actor_type' => 'test',
            ]);

            expect($model)->toBeInstanceOf(Action::class)
                ->and($model->actionable_id)->toBe(1)
                ->and($model->actor_id)->toBe(1);
        });

        it('creates attachment model instance', function () {
            $model = Wirechat::attachmentModel([
                'file_name' => 'test.jpg',
                'original_name' => 'test.jpg',
            ]);

            expect($model)->toBeInstanceOf(Attachment::class)
                ->and($model->file_name)->toBe('test.jpg')
                ->and($model->original_name)->toBe('test.jpg');
        });

        it('creates conversation model instance', function () {
            $model = Wirechat::conversationModel([
                'disappearing_duration' => 3600,
            ]);

            expect($model)->toBeInstanceOf(Conversation::class)
                ->and($model->disappearing_duration)->toBe(3600);
        });

        it('creates group model instance', function () {
            $model = Wirechat::groupModel(['name' => 'Test Group']);

            expect($model)->toBeInstanceOf(Group::class)
                ->and($model->name)->toBe('Test Group');
        });

        it('creates message model instance', function () {
            $model = Wirechat::messageModel(['body' => 'Hello World']);

            expect($model)->toBeInstanceOf(Message::class)
                ->and($model->body)->toBe('Hello World');
        });

        it('creates participant model instance', function () {
            $model = Wirechat::participantModel([
                'participantable_id' => 1,
                'participantable_type' => 'User',
            ]);

            expect($model)->toBeInstanceOf(Participant::class)
                ->and($model->participantable_id)->toBe(1)
                ->and($model->participantable_type)->toBe('User');
        });
    });

    describe('Model Class Validation', function () {
        afterEach(function () {
            // Reset all model configs to defaults after each test
            config([
                'wirechat.models.action' => Action::class,
                'wirechat.models.attachment' => Attachment::class,
                'wirechat.models.conversation' => Conversation::class,
                'wirechat.models.group' => Group::class,
                'wirechat.models.message' => Message::class,
                'wirechat.models.participant' => Participant::class,
            ]);
        });

        it('throws exception for non-existent class', function () {
            config(['wirechat.models.action' => 'NonExistentClass']);

            Wirechat::actionModelClass();
        })->throws(InvalidArgumentException::class, "Model class 'NonExistentClass' configured in 'wirechat.models.action' does not exist.");

        it('throws exception for class that does not extend base class', function () {
            config(['wirechat.models.action' => stdClass::class]);

            Wirechat::actionModelClass();
        })->throws(InvalidArgumentException::class, "Model class 'stdClass' configured in 'wirechat.models.action' must extend '".Action::class."'.");

        it('validates model classes for all model types', function () {
            $modelTypes = [
                'action' => Action::class,
                'attachment' => Attachment::class,
                'conversation' => Conversation::class,
                'group' => Group::class,
                'message' => Message::class,
                'participant' => Participant::class,
            ];

            foreach ($modelTypes as $type => $expectedClass) {
                config(["wirechat.models.{$type}" => 'NonExistentClass']);

                $method = "{$type}ModelClass";

                expect(fn () => Wirechat::{$method}())
                    ->toThrow(InvalidArgumentException::class, "Model class 'NonExistentClass' configured in 'wirechat.models.{$type}' does not exist.");

                // Reset to valid class for next iteration
                config(["wirechat.models.{$type}" => $expectedClass]);
            }
        });
    });

    describe('Model Table Names', function () {
        it('returns correct table names for all models', function () {
            expect(Wirechat::actionModelTable())->toBe((new Action)->getTable())
                ->and(Wirechat::attachmentModelTable())->toBe((new Attachment)->getTable())
                ->and(Wirechat::conversationModelTable())->toBe((new Conversation)->getTable())
                ->and(Wirechat::groupModelTable())->toBe((new Group)->getTable())
                ->and(Wirechat::messageModelTable())->toBe((new Message)->getTable())
                ->and(Wirechat::participantModelTable())->toBe((new Participant)->getTable());
        });
    });

    describe('Custom Model Classes', function () {
        it('works with custom model classes that extend base classes', function () {
            // Store original config value to restore later
            $originalActionClass = config('wirechat.models.action');

            // Create a temporary custom model class for testing
            $customActionClass = new class extends Action
            {
                public function customMethod(): string
                {
                    return 'custom';
                }
            };

            config(['wirechat.models.action' => get_class($customActionClass)]);

            expect(Wirechat::actionModelClass())->toBe(get_class($customActionClass));

            $instance = Wirechat::actionModel();
            expect($instance)->toBeInstanceOf(get_class($customActionClass))
                ->and($instance->customMethod())->toBe('custom');

            // Reset config to prevent leakage to other tests
            config(['wirechat.models.action' => $originalActionClass]);
        });
    });
});
