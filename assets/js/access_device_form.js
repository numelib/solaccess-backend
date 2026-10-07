import Api from "./api.js";

export function accessDeviceForm() {
    const ownerSelect = document.getElementById("AccessDevice_user");
    const fieldCollectionItems = document.querySelectorAll("[data-field-collection=codes] .field-collection-item, [data-field-collection=badges] .field-collection-item");
    fieldCollectionItems.forEach((element) => handleContactModeChange(element));

    const userId = Number.parseInt(ownerSelect.value, 10);
    handleDeviceOwnerChange(userId);

    ownerSelect.addEventListener("change", async (ev) => {
        try {
            const userId = Number.parseInt(ownerSelect.value, 10);
            await handleDeviceOwnerChange(userId);
        } catch (error) {
            console.error(error);
        }
    });

    document.addEventListener("ea.collection.item-added", async (ev) => {
        try {
            const { newElement } = ev.detail;
            handleContactModeChange(newElement);

            const userId = Number.parseInt(ownerSelect.value, 10);
            await handleDeviceOwnerChange(userId);
        } catch (error) {
            console.error(error);
        }
    });
}

/**
 * @param { number } userId
 */
export async function handleDeviceOwnerChange(userId) {
    /** @type { HTMLSelectElement[] } */
    const contactsSelects = Array.from(document.querySelectorAll("[data-field-collection=codes] .field-collection-item select, [data-field-collection=badges] .field-collection-item select"));
    const { contacts } = await Api.getUserContacts(userId);
    contactsSelects.forEach((elem) => {
        const oldValue = elem.value;
        const options = elem.options;
        for (let i = options.length - 1; i >= 0; i--) {
            elem.options.remove(i);
        }

        let newValue = "";
        contacts.forEach((contact) => {
            const option = document.createElement("option");
            option.value = contact.id;
            option.textContent = `${contact.firstname} ${contact.lastname}`;

            elem.appendChild(option);

            if (oldValue !== "" && contact.id === Number.parseInt(oldValue, 10)) {
                newValue = contact.id;
            }
        });
        elem.value = newValue;
    });
}

/**
 * @param { HTMLElement } collectionItem - Collection item of EasyAdmin
 */
export function handleContactModeChange(collectionItem) {
    const currentMode = collectionItem.querySelector(".contact-mode input:checked").value;
    const contactModeInputs = collectionItem.querySelectorAll(".contact-mode input");
    const contactSelectGroup = collectionItem.querySelector("select")?.closest(".form-group");
    const contactCreateGroups = Array.from(collectionItem.querySelectorAll("input.contact-choice-new")).map((collectionItem) => collectionItem.closest(".form-group"));

    if (currentMode === "select") {
        contactSelectGroup.style.display = "";
        contactCreateGroups.forEach((element) => (element.style.display = "none"));
    }

    if (currentMode === "create") {
        contactSelectGroup.style.display = "none";
        contactCreateGroups.forEach((element) => (element.style.display = ""));
    }

    contactModeInputs.forEach((input) => {
        input.addEventListener("change", (ev) => {
            const mode = input.value;
            if (mode === "select") {
                contactSelectGroup.style.display = "";
                contactCreateGroups.forEach((element) => (element.style.display = "none"));
            }

            if (mode === "create") {
                contactSelectGroup.style.display = "none";
                contactCreateGroups.forEach((element) => (element.style.display = ""));
            }
        });
    });
}
