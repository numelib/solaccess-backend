// @ts-check

/** @import { LockState } from "./Dto/LockStatesDto.js" */

/**
 * @typedef Translations
 * @property { { unlock_device: string } } command_type
 * @property { { timeout: string, processed: string, in_progress: string } } command_status
 * @property { { locked: string, unlocked: string } } lock_states
 */

import { isObject } from "./validation.js";

export function load() {
    if ("translations" in window === false) {
        throw new Error("Translations were not found inside the window object");
    }

    const translations = window.translations;

    if (!isTranslationsObj(translations)) {
        throw new Error("Translations object is not valid");
    }

    return translations;
}
/**
 * @param { unknown } val
 * @returns { val is Translations }
 */
export function isTranslationsObj(val) {
    if (!isObject(val)) {
        return false;
    }

    if ("command_type" in val === false || "command_status" in val === false || "lock_states" in val === false) {
        return false;
    }

    const commandStatus = val["command_status"];
    const commandType = val["command_type"];
    const lockStates = val["lock_states"];
    if (!isObject(commandType) || !isObject(commandStatus) || !isObject(lockStates)) {
        return false;
    }

    if ("unlock_device" in commandType === false || "timeout" in commandStatus === false || "processed" in commandStatus === false || "in_progress" in commandStatus === false) {
        return false;
    }

    if ("locked" in lockStates === false || "unlocked" in lockStates === false) {
        return false;
    }

    return true;
}

/**
 * @param { Translations } translations
 * @param { string } type
 */
export function getCommandType(translations, type) {
    if (type === "unlock_device") {
        return translations["command_type"]["unlock_device"];
    }

    throw new Error(`Undefined command type "${type}"`);
}

/**
 *
 * @param { Translations } translations
 * @param { LockState } lockState
 */
export function getLockStateLabel(translations, lockState) {
    switch (lockState) {
        case "LOCKED":
            return translations["lock_states"]["locked"];
        case "UNLOCKED":
            return translations["lock_states"]["unlocked"];
    }

    throw new Error(`Unknown lock state "${lockState}"`);
}

/**
 *
 * @param { Translations } translations
 * @param { string } status
 * @returns { string }
 */
export function getCommandStatus(translations, status) {
    switch (status) {
        case "timeout":
            return translations["command_status"]["timeout"];
        case "processed":
            return translations["command_status"]["processed"];
        case "in_progress":
            return translations["command_status"]["in_progress"];
        default:
            throw new Error(`Undefined command status "${status}"`);
    }
}

export default {
    load,
    isTranslationsObj,
    getCommandType,
    getCommandStatus,
    getLockStateLabel,
};
