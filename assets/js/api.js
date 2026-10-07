// @ts-check

import { CommandsStatusDto } from "./Dto/CommandsStatusDto.js";
import { UnlockDeviceDto } from "./Dto/UnlockDeviceDto.js";
import UserContactsDto from "./Dto/UserContactsDto.js";

/**
 * @param { number } deviceId
 * @param { number } page
 * @param { number|null } since
 * @param { AbortController } controller
 * @returns { Promise<CommandsStatusDto> }
 */
export async function getCommandsStatus(deviceId, page, since, controller) {
    let url = `/admin/access-device/${deviceId}/status?page=${page}`;
    if (since !== null) {
        url += `&since=${since}`;
    }

    const response = await fetch(url, {
        method: "GET",
        signal: controller.signal,
    });
    const data = await response.json();
    const dto = CommandsStatusDto.fromObject(data);
    return dto;
}

/**
 * @param { number } id
 */
export async function unlockDevice(id) {
    const actionUrl = `/admin/access-device/${id}/unlock`;
    const response = await fetch(actionUrl, { method: "GET" });
    const data = await response.json();
    const unlockDeviceDto = UnlockDeviceDto.fromObject(data);
}

/**
 * @param { number } id User id
 * @returns { Promise<UserContactsDto> }
 */
export async function getUserContacts(id) {
    const response = await fetch(`/admin/user-admin/${id}/contacts`);
    const json = await response.json();

    return UserContactsDto.fromObject(json);
}

export default {
    getCommandsStatus,
    unlockDevice,
    getUserContacts,
};
