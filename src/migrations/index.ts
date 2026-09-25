import * as migration_20260925_144041_initial from './20260925_144041_initial';

export const migrations = [
  {
    up: migration_20260925_144041_initial.up,
    down: migration_20260925_144041_initial.down,
    name: '20260925_144041_initial'
  },
];
