// @ts-check

/**
 * @typedef { 'UNLOCKED'|'LOCKED' } LockState
 */

import { isAccessDeviceLockState, lockStates } from "../validation.js";

export class LockStatesDto {
    #lockStates;

    /**
     * @param { {[key:number]: LockState} } lockStatesById
     */
    constructor(lockStatesById) {
        this.#lockStates = lockStatesById;
    }

    all() {
        return this.#lockStates;
    }

    /** @param { number } id */
    getById(id) {
        return this.#lockStates[id];
    }

    /**
     * @param { object } obj
     * @returns { LockStatesDto }
     */
    static fromObject(obj) {
        const entries = Object.entries(obj);
        /** @type { {[key:number]: LockState } } */
        const lockStatesById = {};
        for (const [key, value] of entries) {
            const id = Number.parseInt(key, 10);
            if (Number.isNaN(id)) {
                throw new Error(`Key "${key} is not a valid ID`);
            }

            if (!isAccessDeviceLockState(value)) {
                throw new Error(`Value "${value}" is not a valid lock state`);
            }

            lockStatesById[id] = value;
        }

        return new LockStatesDto(lockStatesById);
    }
}
