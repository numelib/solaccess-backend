// @ts-check

import { isObject } from "../validation.js";
import ContactDto from "./ContactDto.js";

export default class UserContactsDto {
    /**
     * @param { ContactDto[] } contacts
     */
    constructor(contacts) {
        this.contacts = contacts;
    }

    /**
     * @param { unknown } obj
     */
    static fromObject(obj) {
        /** @type { (arg0: string) => Error} */
        const undefinedPropError = (prop) => new Error(`Property "${prop}" doesn't exist in ${obj}`);
        /** @type { (arg0: string, arg1: string) => Error} */
        const propTypeError = (prop, type) => new Error(`Property "${prop}" must be of type ${type}`);

        if (!isObject(obj)) throw new Error(`Argument "obj" must be an object`);
        if ("contacts" in obj === false) throw undefinedPropError("contacts");
        if (typeof obj.contacts !== "object" || !Array.isArray(obj.contacts)) throw propTypeError("contacts", "array");

        const contacts = obj.contacts.map(ContactDto.fromObject);
        return new UserContactsDto(contacts);
    }
}
