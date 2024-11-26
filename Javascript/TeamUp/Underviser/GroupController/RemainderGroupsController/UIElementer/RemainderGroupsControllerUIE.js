class RemainderGroupControllerUIE extends UIElement
{
    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            = `
<div>
    <p>Her er gruppene som ikke har blitt godkjent:</p>
    <div data-anchor=${RemainderGroups.name}></div>
</div>
            `;
        super(htmlString,nexus);
    }
}