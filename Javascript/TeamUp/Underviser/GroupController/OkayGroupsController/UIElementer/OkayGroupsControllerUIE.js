class OkayGroupsControllerUIE extends UIElement
{
    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            = `
<div>
    <p>Her er gruppene som er godkjent:</p>
    <div data-anchor=${OkayGroups.name}></div>
</div>
            `;
        super(htmlString,nexus);
    }
}