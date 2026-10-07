export const lockStates = ["LOCKED", "UNLOCKED"];

/**
 * @param { unknown } val
 * @returns { val is object }
 */
export function isObject(val) {
    if (
        typeof val !== "object" ||
        val === null ||
        Array.isArray(val) === true
    ) {
        return false;
    }

    return true;
}

/**
 * @param { unknown } val
 * @returns { val is 'LOCKED'|'UNLOCKED' }
 */
export function isAccessDeviceLockState(val) {
    return typeof val === "string" && lockStates.includes(val);
}

/**
 * @template {object} T
 * @param {T} obj
 * @param {PropertyKey} key
 * @returns {key is keyof T}
 */
export function hasKey(obj, key) {
    return key in obj;
}

export default {
    isObject,
    hasKey,
};
