/* 关卡定义源（落子表形)
 * 字母：K母 Q继位母 P子 G军 S探 D盾 W白板；大写红、小写蓝。坐标 [列, 行]
 */
"use strict";

// 双方王座（红在上 r=0，蓝在下 r=28）
const RK = [[14, 0, "P"], [15, 0, "K"]];
const BK = [[14, 28, "p"], [15, 28, "k"]];
const ROYAL = RK.concat(BK);
const row = (c0, r, s) => s.split("").map((ch, i) => [c0 + i, r, ch]);
module.exports = [
  {
    id: "t1", chapter: 1, name: "初阵：军棋直取",
    hint: "军棋横纵斜均可走，步长 3 起；敌方军棋越少，你的军棋走得越远。选中红军棋，吃掉右侧的蓝军棋。",
    place: ROYAL.concat([[10, 10, "G"], [13, 10, "g"]]),
    tail: "r 8 - - - - - -",
    goal: { type: "slay", side: "red", target: "general", moveLimit: 1 },
    ai: 3, verify: "proof",
  },
  {
    id: "t2", chapter: 1, name: "盾之侧翼",
    hint: "对方子棋在场时，盾棋只能被「横向」吃掉。上方那枚军棋纵向攻不进，请用左侧军棋沿同一行推进。",
    place: ROYAL.concat([[6, 10, "G"], [10, 7, "G"], [10, 10, "d"]]),
    tail: "r 9 - - - - - -",
    goal: { type: "slay", side: "red", target: "shield", moveLimit: 1 },
    ai: 3, verify: "proof",
  },
  {
    id: "t3", chapter: 1, name: "探棋开路",
    hint: "棋子多于 20 枚时受迷雾与许可区约束：只有探棋、本方核心区的棋子能自由行动。先把探棋移到军棋旁，许可区与视野一起覆盖它，军棋才能出手。",
    place: ROYAL.concat([
      [7, 11, "S"], [10, 10, "G"], [13, 10, "g"],
      ...row(0, 12, "GGGGG"), ...row(20, 12, "DDDD"),
      ...row(0, 17, "ggggg"), ...row(20, 17, "dddd"),
    ]),
    tail: "r 12 7.11 - - - - -",
    goal: { type: "slay", side: "red", target: "general", count: 1, moveLimit: 2 },
    ai: 3, verify: "proof",
  },
  {
    id: "t4", chapter: 1, name: "渡河",
    hint: "只有军棋能过河，且必须走直线：纵向需步长 ≥3，横向需步长 ≥4，同一河段内不能有别的棋子。把军棋推进到蓝方半场。",
    place: ROYAL.concat([[10, 13, "G"]]),
    tail: "r 15 - - - - - -",
    goal: { type: "reach", side: "red", pieceType: "general", zone: { r0: 16, r1: 29, c0: 0, c1: 29 }, moveLimit: 1 },
    ai: 3, verify: "proof",
  },
  {
    id: "t5", chapter: 1, name: "继位与复活",
    hint: "母棋阵亡后子棋继位（横纵 2 步、可吃子）。继位母棋每吃一子，就在原地复活一枚白板棋。吃掉下方的蓝军棋试试。",
    place: BK.concat([[10, 10, "Q"], [10, 12, "g"]]),
    tail: "r 26 - - - - - -",
    goal: { type: "slay", side: "red", target: "general", moveLimit: 1 },
    ai: 3, verify: "proof",
  },

  /* ================= 第二章================= */
  {
    id: "c1", chapter: 2, name: "破盾阵",
    hint: "盾墙横在面前。绕到同一行再侧击，拿下两枚盾棋。",
    place: ROYAL.concat([
      [4, 10, "G"], [20, 10, "G"],
      [4, 12, "d"], [12, 12, "d"], [20, 12, "d"], [26, 12, "d"],
      [8, 20, "g"], [15, 20, "g"], [22, 20, "g"],
    ]),
    tail: "r 18 - - - - - -",
    goal: { type: "slay", side: "red", target: "shield", count: 2, moveLimit: 16 },
    ai: 3, verify: "playtest",
  },
  {
    id: "c2", chapter: 2, name: "斩首",
    hint: "越过河道，取下蓝方母棋或子棋任意一枚。注意盾棋会在正前方 1~3 格内为同列棋子撑起保护。",
    place: ROYAL.concat([
      [4, 10, "S"], [8, 10, "G"], [15, 10, "G"], [22, 10, "G"],
      [10, 20, "g"], [10, 29, "d"], [20, 29, "d"],
    ]),
    tail: "r 20 4.10 - - - - -",
    goal: { type: "slay", side: "red", target: "royal", count: 1, moveLimit: 24 },
    ai: 2, verify: "playtest",
  },
  {
    id: "c3", chapter: 2, name: "孤城",
    hint: "三路蓝军压境，你只有一枚军棋。母子俱亡即败——撑过 8 个回合。",
    place: ROYAL.concat([
      [15, 10, "G"],
      [8, 18, "g"], [15, 18, "g"], [22, 18, "g"],
    ]),
    tail: "r 22 - - - - - -",
    goal: { type: "survive", side: "red", rounds: 8 },
    ai: 1, verify: "playtest",
  },
  {
    id: "c4", chapter: 2, name: "三军会猎",
    hint: "对面三路军棋，逐个歼灭。你的军棋步长会随蓝方军棋阵亡而增长。",
    place: ROYAL.concat([
      [4, 10, "S"], [8, 10, "G"], [15, 10, "G"], [22, 10, "G"],
      [8, 18, "g"], [15, 18, "g"], [22, 18, "g"], [8, 22, "g"], [15, 22, "g"], [22, 22, "g"],
      [12, 26, "d"], [18, 26, "d"], [6, 26, "d"], [24, 26, "d"],
    ]),
    tail: "r 24 4.10 - - - - -",
    goal: { type: "slay", side: "red", target: "general", count: 3, moveLimit: 32 },
    ai: 2, verify: "playtest",
  },
  {
    id: "c5", chapter: 2, name: "终局",
    hint: "先杀子棋，再取母棋：若母棋先亡，子棋会继位，且继位母棋每吃一子就复活一枚白板。子棋被同列盾棋护住，先沿底行横向拆掉那枚盾。",
    proxy: 0,
    place: ROYAL.concat([
      [4, 10, "S"], [8, 10, "G"], [12, 10, "G"], [18, 10, "G"], [22, 10, "G"],
      [14, 29, "d"], [16, 29, "d"],
    ]),
    tail: "r 28 4.10 - - - - -",
    goal: { type: "standard", side: "red", moveLimit: 70 },
    ai: 2, verify: "playtest",
  },
  {
    id: "c6", chapter: 2, name: "缺口穿渡",
    hint: "河道并非连成一片：每 8 列留 2 列缺口。探棋横纵 4 步，可从缺口直插对岸；但身边贴着棋子时过不去。",
    place: ROYAL.concat([
      [14, 12, "S"], [22, 11, "G"],
      [8, 20, "g"], [20, 22, "g"], [12, 26, "d"], [18, 26, "d"],
    ]),
    tail: "r 16 14.12 - - - - -",
    goal: { type: "reach", side: "red", pieceType: "scout", zone: { r0: 16, r1: 29, c0: 0, c1: 29 }, moveLimit: 12 },
    proxy: 0,
    ai: 2, verify: "playtest",
  },
  {
    id: "c7", chapter: 2, name: "白板反攻",
    hint: "你的母棋已亡、子棋继位。继位母棋横纵 2 步、能吃子，且每吃一子就在原地复活一枚白板棋——吃得越多，队伍越长。",
    place: BK.concat([
      [15, 10, "Q"], [12, 12, "g"], [18, 12, "g"], [15, 14, "g"],
      [6, 26, "d"], [24, 26, "d"],
    ]),
    tail: "r 24 - - - - - -",
    goal: { type: "slay", side: "red", target: "general", count: 2, moveLimit: 14 },
    ai: 2, verify: "playtest",
  },
  {
    id: "c8", chapter: 2, name: "执蓝守土",
    hint: "这局你执蓝方（棋盘下方）。红方三路军棋压过河来，你只有两枚军棋加一道盾——撑过 10 个回合。",
    place: ROYAL.concat([
      [8, 12, "G"], [15, 12, "G"], [22, 12, "G"],
      [10, 20, "g"], [20, 20, "g"], [15, 24, "d"],
    ]),
    tail: "r 20 - - - - - -",
    goal: { type: "survive", side: "blue", rounds: 10 },
    ai: 2, verify: "playtest",
  },
  {
    id: "c9", chapter: 2, name: "无雾终盘",
    hint: "全场棋子已不足 20 枚：迷雾自然解除、全体自由行动，没有许可区可躲。硬碰硬把对面军棋清干净。",
    place: ROYAL.concat([
      [6, 10, "G"], [12, 10, "G"], [18, 10, "G"], [24, 10, "G"],
      [8, 19, "g"], [16, 19, "g"], [22, 21, "g"], [15, 27, "d"],
    ]),
    tail: "r 30 - - - - - -",
    goal: { type: "slay", side: "red", target: "general", count: 3, moveLimit: 28 },
    ai: 2, verify: "playtest",
  },
  {
    id: "c10", chapter: 2, name: "孤子直取",
    hint: "蓝方只剩母子与一枚盾。盾在子棋正前方为它撑起保护，而盾自己只能被横向吃——先沿同一行拆掉它。",
    place: ROYAL.concat([
      [4, 10, "S"], [10, 10, "G"], [20, 10, "G"],
      [14, 26, "d"],
    ]),
    tail: "r 26 4.10 - - - - -",
    goal: { type: "slay", side: "red", target: "royal", count: 1, moveLimit: 20 },
    ai: 2, verify: "playtest",
  },
];
