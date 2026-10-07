// @ts-check

import { isObject } from "../validation.js";

export default class ContactDto {
    /**
     * @param { number } id
     * @param { string } firstname
     * @param { string } lastname
     */
    constructor(id, firstname, lastname) {
        this.id = id;
        this.firstname = firstname;
        this.lastname = lastname;
    }

    /**
     * @param { unknown } obj
     * @returns { ContactDto }
     */
    static fromObject(obj) {
        if (!isObject(obj)) throw new Error(`Argument "obj" is not an object`);
        /** @type { (arg0: string) => Error} */
        const undefinedPropError = (prop) => new Error(`Property "${prop}" doesn't exist in ${obj}`);
        /** @type { (arg0: string, arg1: string) => Error} */
        const propTypeError = (prop, type) => new Error(`Property "${prop}" must be of type ${type}`);

        if ("id" in obj === false) throw undefinedPropError("id");
        if ("firstname" in obj === false) throw undefinedPropError("firstname");
        if ("lastname" in obj === false) throw undefinedPropError("lastname");

        if (typeof obj.id !== "number") throw propTypeError("id", "number");
        if (typeof obj.firstname !== "string") throw propTypeError("firstname", "string");
        if (typeof obj.lastname !== "string") throw propTypeError("lastname", "string");

        return new ContactDto(obj.id, obj.firstname, obj.lastname);
    }
}
