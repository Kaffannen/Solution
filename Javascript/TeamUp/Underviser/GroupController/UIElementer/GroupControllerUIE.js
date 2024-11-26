class GroupControllerUIE extends UIElement
{
    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            = `
<div>
    <div data-anchor=${OkayGroupsController.name}></div>
    <div data-anchor=${RemainderGroupsController.name}></div>
</div>
            `;
        super(htmlString,nexus);
    }
}