import { beforeEach, describe, expect, it } from 'vitest';
import { pendingTasks } from './pending-tasks';

describe('pendingTasks', () => {
  beforeEach(() => window.sessionStorage.clear());

  it('keeps each task once and survives a reload of the module state', () => {
    pendingTasks.add('a');
    pendingTasks.add('a');
    pendingTasks.add('b');

    expect(pendingTasks.list()).toEqual(['a', 'b']);
  });

  it('reports whether a removed task was still pending', () => {
    pendingTasks.add('a');

    expect(pendingTasks.remove('a')).toBe(true);
    expect(pendingTasks.remove('a')).toBe(false);
    expect(pendingTasks.list()).toEqual([]);
  });

  it('ignores broken storage content', () => {
    window.sessionStorage.setItem('second-memory.pending-background-tasks', '{oops');

    expect(pendingTasks.list()).toEqual([]);
  });
});
