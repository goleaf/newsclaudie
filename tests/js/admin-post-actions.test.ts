import assert from 'node:assert/strict';
import { describe, it } from 'node:test';

import { adminPostActions } from '../../resources/js/admin-post-actions';

const wait = (ms = 0) => new Promise((resolve) => setTimeout(resolve, ms));

describe('adminPostActions', () => {
    it('optimistically updates state and marks lag after threshold', async () => {
        let resolvePublish: (() => void) | null = null;
        const publishPromise = new Promise<void>((resolve) => {
            resolvePublish = resolve;
        });

        const actions = adminPostActions({
            initialStates: { 1: false },
            defaultError: 'fallback',
        });

        actions.$wire = {
            publish: async (id: number) => {
                assert.equal(id, 1);
                return publishPromise;
            },
            unpublish: async () => Promise.resolve(),
        };

        actions.queueAction('publish', 1, false);

        assert.equal(actions.isPublished(1, false), true, 'optimistic flip should occur immediately');
        assert.equal(actions.isPending(1), true);
        assert.equal(actions.isInFlight(1), true);
        assert.equal(actions.isLagging(1), false);

        await wait(575);
        assert.equal(actions.isLagging(1), true, 'lag flag should set after threshold while in flight');

        resolvePublish?.();
        await wait(0);

        assert.equal(actions.isPending(1), false);
        assert.equal(actions.isLagging(1), false);
        assert.equal(actions.isPublished(1), true);
        assert.equal(actions.errorMessage, '');
    });

    it('reverts state and surfaces error on failure while continuing queue', async () => {
        const actions = adminPostActions({
            initialStates: { 2: true },
            defaultError: 'fallback',
        });

        actions.$wire = {
            publish: async () => Promise.resolve(),
            unpublish: async () => {
                throw new Error('boom');
            },
        };

        actions.queueAction('unpublish', 2, true);

        await wait(10);

        assert.equal(actions.isPublished(2, true), false, 'optimistic drop to draft before confirmation');
        assert.equal(actions.isPending(2), true);

        await wait(10);

        assert.equal(actions.isPending(2), false);
        assert.equal(actions.isPublished(2, true), true, 'state should revert to confirmed value on failure');
        assert.match(actions.errorMessage, /boom|fallback/);
    });

    it('processes queued actions sequentially', async () => {
        const order: Array<string> = [];

        const actions = adminPostActions({
            initialStates: { 3: false, 4: true },
            defaultError: 'fallback',
        });

        actions.$wire = {
            publish: async (id: number) => {
                order.push(`publish:${id}`);
                await wait(25);
            },
            unpublish: async (id: number) => {
                order.push(`unpublish:${id}`);
                await wait(25);
            },
        };

        actions.queueAction('publish', 3, false);
        actions.queueAction('unpublish', 4, true);

        assert.equal(actions.queueSize(), 2);

        await wait(80);

        assert.deepEqual(order, ['publish:3', 'unpublish:4']);
        assert.equal(actions.isPending(3), false);
        assert.equal(actions.isPending(4), false);
        assert.equal(actions.isPublished(3, false), true);
        assert.equal(actions.isPublished(4, true), false);
    });
});
