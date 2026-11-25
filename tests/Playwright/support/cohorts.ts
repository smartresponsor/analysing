export type Cohort = 'existing' | 'new' | 'virtual';

export function existingUser() {
  return {
    id: 'existing-user-1',
    email: 'existing.user@example.test',
    cohort: 'existing' as Cohort,
  };
}

export function newUser() {
  return {
    id: 'new-user-1',
    email: 'new.user@example.test',
    cohort: 'new' as Cohort,
  };
}

export function virtualUser() {
  return {
    id: 'virtual-user-1',
    email: 'virtual.user@example.test',
    cohort: 'virtual' as Cohort,
  };
}
