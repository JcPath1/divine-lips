import {
  AbsoluteFill,
  CanvasImage,
  Composition,
  Easing,
  Interactive,
  interpolate,
  staticFile,
  useCurrentFrame,
  useVideoConfig,
} from "remotion";
import { loadFont as loadDisplay } from "@remotion/google-fonts/CormorantGaramond";
import { loadFont as loadText } from "@remotion/google-fonts/Jost";

// Brand kit: brand/brand-guidelines.md and brand/design-tokens.json
const { fontFamily: display } = loadDisplay("normal", {
  weights: ["600"],
  subsets: ["latin"],
});
loadDisplay("italic", { weights: ["500"], subsets: ["latin"] });
const { fontFamily: text } = loadText("normal", {
  weights: ["400", "500", "600"],
  subsets: ["latin"],
});

const INK = "#1a1a1a";
const POP = "#e6337f";
const EASE = Easing.bezier(0.16, 1, 0.3, 1);
const CLAMP = {
  extrapolateLeft: "clamp",
  extrapolateRight: "clamp",
} as const;

export const MyComposition = () => {
  return (
    <Composition
      id="GlimmerKissReel"
      component={GlimmerKissReel}
      durationInFrames={300}
      fps={30}
      width={1080}
      height={1920}
    />
  );
};

// The background starts clear (white) and turns pink, like the gloss.
const Background = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  return (
    <AbsoluteFill style={{ backgroundColor: "#ffffff" }}>
      <Interactive.Div
        name="Pink wash"
        style={{
          position: "absolute",
          inset: 0,
          background:
            "linear-gradient(180deg, #ffffff 0%, #fdeef1 34%, #fbd3e0 70%, #f48fb6 100%)",
          opacity: interpolate(frame, [3 * fps, 5.2 * fps], [0, 1], {
            ...CLAMP,
            easing: Easing.bezier(0.4, 0, 0.2, 1),
          }),
        }}
      />
    </AbsoluteFill>
  );
};

const Logo = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  return (
    <CanvasImage
      src={staticFile("logo.png")}
      style={{
        position: "absolute",
        left: 80,
        top: 200,
        width: 210,
        opacity: interpolate(frame, [0, 0.6 * fps], [0, 1], CLAMP),
      }}
    />
  );
};

const Lead = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  return (
    <div
      style={{
        position: "absolute",
        left: 80,
        top: 380,
        fontFamily: display,
        fontWeight: 600,
        fontSize: 124,
        lineHeight: 0.98,
        color: INK,
      }}
    >
      <Interactive.Div
        name="Goes on clear"
        style={{
          opacity: interpolate(frame, [0.5 * fps, 1.3 * fps], [0, 1], CLAMP),
          translate: interpolate(
            frame,
            [0.5 * fps, 1.5 * fps],
            ["0px 40px", "0px 0px"],
            { ...CLAMP, easing: EASE },
          ),
        }}
      >
        Goes on
        <br />
        clear.
      </Interactive.Div>
      <Interactive.Div
        name="Turns"
        style={{
          opacity: interpolate(frame, [2.4 * fps, 3 * fps], [0, 1], CLAMP),
          translate: interpolate(
            frame,
            [2.4 * fps, 3.2 * fps],
            ["0px 40px", "0px 0px"],
            { ...CLAMP, easing: EASE },
          ),
        }}
      >
        Turns
      </Interactive.Div>
    </div>
  );
};

const Bottle = () => {
  const frame = useCurrentFrame();
  const { fps, durationInFrames } = useVideoConfig();
  return (
    <CanvasImage
      src={staticFile("glimmer-kiss.png")}
      style={{
        position: "absolute",
        left: 716,
        top: 470,
        width: 290,
        filter: "drop-shadow(0 34px 38px rgba(0,0,0,0.22))",
        opacity: interpolate(frame, [0.2 * fps, 0.9 * fps], [0, 1], CLAMP),
        translate: interpolate(
          frame,
          [0.2 * fps, 1.8 * fps],
          ["0px 140px", "0px 0px"],
          { ...CLAMP, easing: EASE },
        ),
        rotate: interpolate(
          frame,
          [0, durationInFrames],
          ["3deg", "9deg"],
          CLAMP,
        ),
      }}
    />
  );
};

// The giant word wipes in left to right and prints over the bottle.
const BigPink = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const hidden = interpolate(frame, [3.2 * fps, 4.8 * fps], [100, 0], {
    ...CLAMP,
    easing: Easing.bezier(0.65, 0, 0.35, 1),
  });
  return (
    <Interactive.Div
      name="pink."
      style={{
        position: "absolute",
        left: 22,
        top: 700,
        fontFamily: display,
        fontStyle: "italic",
        fontWeight: 500,
        fontSize: 600,
        lineHeight: 1,
        letterSpacing: "-0.02em",
        whiteSpace: "nowrap",
        color: POP,
        mixBlendMode: "multiply",
        paddingRight: 60,
        clipPath: `inset(-10% ${hidden}% -30% 0)`,
        scale: interpolate(frame, [3.2 * fps, 10 * fps], [1, 1.04], {
          ...CLAMP,
          output: "perceptual-scale",
        }),
      }}
    >
      pink.
    </Interactive.Div>
  );
};

const Sub = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  return (
    <Interactive.Div
      name="Supporting line"
      style={{
        position: "absolute",
        left: 80,
        top: 1385,
        width: 580,
        fontFamily: text,
        fontWeight: 400,
        fontSize: 46,
        lineHeight: 1.35,
        color: INK,
        opacity: interpolate(frame, [5.4 * fps, 6.2 * fps], [0, 1], CLAMP),
        translate: interpolate(
          frame,
          [5.4 * fps, 6.4 * fps],
          ["0px 30px", "0px 0px"],
          { ...CLAMP, easing: EASE },
        ),
      }}
    >
      In a few seconds. A different pink on everyone.
    </Interactive.Div>
  );
};

const Footer = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  return (
    <Interactive.Div
      name="Footer"
      style={{
        position: "absolute",
        left: 80,
        top: 1538,
        fontFamily: text,
        fontWeight: 600,
        fontSize: 34,
        letterSpacing: "0.2em",
        textTransform: "uppercase",
        color: INK,
        opacity: interpolate(frame, [7.2 * fps, 8 * fps], [0, 1], CLAMP),
      }}
    >
      Glimmer Kiss &nbsp;$15 &nbsp; glimmerbliss.com
    </Interactive.Div>
  );
};

export const GlimmerKissReel: React.FC = () => {
  return (
    <AbsoluteFill>
      <Background />
      <Logo />
      <Bottle />
      <Lead />
      <BigPink />
      <Sub />
      <Footer />
    </AbsoluteFill>
  );
};
