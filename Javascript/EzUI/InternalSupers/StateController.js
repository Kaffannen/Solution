export default class StateController {
    /**
     * A map of the statefunctions
     * @type {Map<String,Function>}
     */
    #statesMap = new Map();

    /**
     * The current state
     * @type {String}
     */
    #state;

    /**
     * constructor
     */
    constructor() {
        this._parseStates();
    }

    /**
     * Private postconstruct method
     * @return {StateController}
     */
    _parseStates(){
        if (!this.constructor.STATES)
            throw new Error(this.constructor.name + " doesnt have a static declaration of states");
        for (let key in this.constructor.STATES){
            let f = this.constructor.STATES[key];
            this.#statesMap.set(key,f);
        }
        return this;
    }

    /**
     * Sets the state and triggers the corresponding statefunction
     * @param {Function} state
     * @return {StateController}
     */
    setState(state) {
        this.#statesMap.get(state.name).call(this);
        this.#state = state.name;
        return this;
    }

    /**
     * Returns the state
     * @return {String}
     */
    getState(){
        return this.#state;
    }
}