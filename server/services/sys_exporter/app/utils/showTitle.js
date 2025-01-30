import packageData from "../../package.json" with { type: "json" };
import gradient from "gradient-string";
import figlet from "figlet";

export const showTitle = () => {
    const title  = "smart-yard prometheus exporter"
    const version = `version ${packageData.version}`;
    const fonts = ["Calvin S", "Elite", "Pagga"];
    console.log(
        gradient.vice.multiline(
            [
                figlet.textSync(title, {
                    font: fonts[Math.floor(Math.random() * fonts.length)],
                    verticalLayout: "fitted",
                    width: 200,
                }),
                version,
            ].join("\n")
        )
    );
}