// @ts-check

import Translation from "./translation.js";
import { getCrudPage, getEntityId } from "./utils.js";
import { accessDeviceDetail } from "./access_device_detail.js";
import { accessDeviceIndex } from "./access_device_index.js";
import { accessDeviceForm } from "./access_device_form.js";

const translations = Translation.load();
const crudPage = getCrudPage();

document.addEventListener("DOMContentLoaded", () => {
    if (crudPage === null) return;

    switch (crudPage) {
        case "detail":
            const entityId = getEntityId();
            if (entityId === null) {
                throw new Error("Undefined entity ID");
            }

            accessDeviceDetail(entityId);
            break;
        case "index":
            accessDeviceIndex(translations);
            break;

        case "edit":
        case "new":
            accessDeviceForm();
    }
});
