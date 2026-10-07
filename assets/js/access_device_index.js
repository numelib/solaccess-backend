// @ts-check

/** @import { LockState } from "./Dto/LockStatesDto.js" */
/** @import { Translations } from "./translation.js" */

import { createLockIcon, createUnlockIcon, getAttributeAsNumber, selectElement } from "./dom.js";
import { LockStatesDto } from "./Dto/LockStatesDto.js";
import Translation from "./translation.js";
import { isAccessDeviceLockState } from "./validation.js";

/**
 * @param { Translations } translations
 */
export function accessDeviceIndex(translations) {
    const lockIcon = createLockIcon("white");
    const unlockIcon = createUnlockIcon("white");
    const interval = setInterval(async () => {
        try {
            const entityIds = getEntityIds();
            const lockStates = await getLocksStates(entityIds);
            for (const [entityId, lockState] of Object.entries(lockStates.all())) {
                setLockState(Number.parseInt(entityId, 10), lockState, translations);
            }
        } catch (error) {
            clearInterval(interval);
            console.error(error);
            return;
        }
    }, 1000);
}

/**
 * @param { number[] } accessDevicesIds
 * @returns { Promise<LockStatesDto> }
 */
async function getLocksStates(accessDevicesIds) {
    const params = new URLSearchParams();
    accessDevicesIds.forEach((id) => params.append("ids[]", id.toString()));

    const response = await fetch(`/admin/access-device/lock-states?${params.toString()}`);
    const data = await response.json();
    const lockStates = LockStatesDto.fromObject(data);

    return lockStates;
}

/**
 * @returns { number[] }
 */
function getEntityIds() {
    return Array.from(document.querySelectorAll("tr[data-id]")).map((element) => getAttributeAsNumber(element, "data-id"));
}

/**
 * @param { number } entityId
 * @param { LockState } lockState
 * @param { Translations } translations
 */
function setLockState(entityId, lockState, translations) {
    const element = selectElement(document, `tr[data-id="${entityId}"] td[data-column="lockState"] > *`);

    const currentState = getLockState(entityId);
    if (currentState !== lockState) {
        switch (currentState) {
            case "LOCKED":
                element.classList.replace("badge-danger", "badge-success");
                break;
            case "UNLOCKED":
                element.classList.replace("badge-success", "badge-danger");
                break;
        }

        setLockIcon(entityId, lockState);
        setLockLabel(entityId, Translation.getLockStateLabel(translations, lockState));
    }

    element.setAttribute("data-lock-state", lockState);
}

/**
 * @param { number } entityId
 * @returns { LockState }
 */
function getLockState(entityId) {
    const element = selectElement(document, `tr[data-id="${entityId}"] td[data-column="lockState"] > *`);
    const attr = element.getAttribute("data-lock-state");

    if (isAccessDeviceLockState(attr)) {
        return attr;
    }

    throw new Error(`Unknown lock state "${attr}"`);
}

/**
 * @param { number } entityId
 * @param { LockState } state
 */
function setLockIcon(entityId, state) {
    const icon = selectElement(document, `tr[data-id="${entityId}"] td[data-column="lockState"] .icon`);

    switch (state) {
        case "LOCKED": {
            const newIcon = createLockIcon("currentColor", 12);
            newIcon.classList.add("icon");
            icon.replaceWith(newIcon);
        }
        case "UNLOCKED": {
            const newIcon = createUnlockIcon("currentColor", 12);
            newIcon.classList.add("icon");
            icon.replaceWith(newIcon);
        }
    }
}

/**
 * @param { number } entityId
 * @param { string } value
 */
function setLockLabel(entityId, value) {
    const label = selectElement(document, `tr[data-id="${entityId}"] td[data-column="lockState"] .label`);
    label.textContent = value;
}
