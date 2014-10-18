import java.util.*;
import java.io.*;

public class GraphSearch {
	public static void main(String[] args) {
		Map<String, GraphNode> gnMap = buildMap("E.coli");
		printShortestPath("P0ABT2", "P45758", gnMap);
	}
	public static void printShortestPath(String origin, String target, Map<String, GraphNode> nodeMap) {
		GraphNode o = nodeMap.get(origin);
		GraphNode t = nodeMap.get(target);
		Set<GraphNode> visited = new HashSet<GraphNode>();
		visited.add(o);
		ArrayList<GraphNode> daughters = new ArrayList<GraphNode>();
		for (GraphNode g : o.daughters) {
			if(!visited.contains(g)) {
				g.previous = o;
				daughters.add(g);
				visited.add(g);
			}			
		}
		System.out.println(checkDis(t, daughters, visited, "0"));
		while (t != o) {
			System.out.println(t.pname);
			t = t.previous;
		}
		System.out.println(o.pname);
	}
	public static String checkDis(GraphNode t, ArrayList<GraphNode> parents, Set<GraphNode> visited, String distance) {
		if (parents.isEmpty()) return "N/A";
		ArrayList<GraphNode> daughters = new ArrayList<GraphNode>();
		for (GraphNode n : parents) {
			if(n == t) {
				return distance;
			}
			for (GraphNode m : n.daughters) {
				if (!visited.contains(m)) {
					m.previous = n;
					daughters.add(m);
					visited.add(m);
				}
			}
		}
		return checkDis(t, daughters, visited, String.valueOf(Integer.valueOf(distance) + 1));
	}
	public static Map<String, GraphNode> buildMap(String in) {
		Map<String, GraphNode> nodeMap = new HashMap<String, GraphNode>();
		try {
			FileReader  fr = new FileReader(in);
			BufferedReader br = new BufferedReader(fr);
			String line = br.readLine();
			while (line != null) {
				String[] arr = line.split("\t");				
				GraphNode n,m;
				if (!nodeMap.containsKey(arr[0])) {
					n = new GraphNode();
					n.pname = arr[0];
					nodeMap.put(arr[0], n);
				} else {
					n = nodeMap.get(arr[0]);
				}
				if (!nodeMap.containsKey(arr[1])) {
					m = new GraphNode();
					m.pname = arr[1];
					nodeMap.put(arr[1], m);
				} else {
					m = nodeMap.get(arr[1]);
				}
				n.daughters.add(m);
				m.daughters.add(n);
				line = br.readLine();
			}
			br.close();
			fr.close();
		} catch (IOException e) {
			System.err.println(e.getMessage());
		}
		return nodeMap;
	}
}
class GraphNode {
	public ArrayList<GraphNode> daughters;
	public String pname;
	public GraphNode next;
	public GraphNode previous;
	public GraphNode() {
		daughters = new ArrayList<GraphNode>();
		next = null;
		previous = null;
	}
}
